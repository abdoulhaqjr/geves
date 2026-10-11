<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateReport;
use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Mail\CampaignMail;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\User;
use App\Support\EmailTemplates;
use App\Support\EventReport;
use App\Support\EventSettings;
use App\Support\Notifier;
use App\Support\Settings;
use App\Support\XlsxWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Poste de travail du secrétariat (back-office, Blade) : file des rapports de
 * séance à valider, séances en attente de rédaction, rapports validés (onglet
 * « Rapports de séance ») ET un « Secrétariat assisté » qui génère des documents
 * (listes de présence, feuilles d'émargement, affiches directionnelles, ordre du
 * jour…) par prompt ou modèle, via l'IA, ancrés sur les données réelles de
 * l'événement. Scopé aux événements accessibles à l'utilisateur.
 */
class SecretariatController extends Controller
{
    use ScopesEditionAccess;

    /**
     * Modèles de documents proposés au secrétariat. Chaque preset pré-remplit le
     * prompt, le format de sortie et le jeu de données joint.
     *
     * @var array<string, array{label: string, format: string, dataset: string, prompt: string}>
     */
    public const PRESETS = [
        'presence' => [
            'label' => 'Liste de présence',
            'format' => 'xlsx', 'dataset' => 'presence',
            'prompt' => 'Génère une liste de présence des participants de l’événement, avec un en-tête au nom du colloque, et des colonnes : Nom, Institution, Catégorie, Émargement (case à signer).',
        ],
        'emargement' => [
            'label' => 'Feuille d’émargement',
            'format' => 'pdf', 'dataset' => 'presence',
            'prompt' => 'Génère une feuille d’émargement imprimable (en-tête au nom du colloque, date et séance à compléter) avec les colonnes Nom, Institution, Signature.',
        ],
        'affiche' => [
            'label' => 'Affiche directionnelle',
            'format' => 'pdf', 'dataset' => 'none',
            'prompt' => 'Rédige le contenu d’affiches directionnelles pour orienter les participants vers les salles et ateliers, à partir du programme réel (une affiche par direction, avec de gros intitulés « Atelier → Salle »).',
        ],
        'ordre_jour' => [
            'label' => 'Ordre du jour',
            'format' => 'pdf', 'dataset' => 'none',
            'prompt' => 'Rédige l’ordre du jour d’une journée du colloque à partir du programme réel : séances, horaires, salles, modérateurs et rapporteurs.',
        ],
        'chevalets' => [
            'label' => 'Chevalets nominatifs',
            'format' => 'docx', 'dataset' => 'none',
            'prompt' => 'Génère des chevalets nominatifs (nom et fonction) pour les modérateurs et rapporteurs des séances, un par carte, à partir du programme réel.',
        ],
    ];

    /**
     * Documents logistiques standard de l'onglet « Documents », rendus en pages
     * imprimables à partir des données réelles (sans IA). Chaque type a sa vue
     * `secretariat/print/{type}` et quelques paramètres.
     *
     * @var array<string, array{label: string, icon: string, hint: string}>
     */
    public const PRINT_TYPES = [
        'presence' => ['label' => 'Liste de présence', 'icon' => 'users', 'hint' => 'Participants de l’événement, avec colonne d’émargement.'],
        'emargement' => ['label' => 'Feuille d’émargement', 'icon' => 'pen', 'hint' => 'Feuille à signer, en-tête séance/date à compléter.'],
        'affiche' => ['label' => 'Affiche directionnelle', 'icon' => 'pin', 'hint' => 'Panneaux « Atelier → Salle » par salle, en gros.'],
        'ordre_jour' => ['label' => 'Ordre du jour', 'icon' => 'calendar', 'hint' => 'Déroulé d’une journée : séances, horaires, salles, rôles.'],
        'chevalets' => ['label' => 'Chevalets nominatifs', 'icon' => 'card', 'hint' => 'Cartes pliables nom + fonction (modérateurs, rapporteurs).'],
    ];

    public function index(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'editions_read');
        $editionId = $request->has('event_id')
            ? (string) $request->query('event_id', '')
            : $this->currentEditionId($events);
        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;

        /** @var Collection<int, EventSession> $sessions */
        $sessions = $event === null
            ? collect()
            : EventSession::where('event_id', $event->id)
                ->whereNotNull('rapporteur_email')->where('rapporteur_email', '!=', '')
                ->with(['event.series', 'atelier', 'report'])
                ->orderBy('starts_at')->orderBy('id')->get();

        // Statut de chaque séance (une seule classification, lue par les filtres
        // et les compteurs) : à valider → en attente de rédaction → programmée →
        // validée. Un rang de priorité garde les séances actionnables en tête.
        $priority = ['to_validate' => 0, 'awaiting' => 1, 'scheduled' => 2, 'validated' => 3];

        $rows = $sessions->map(function (EventSession $s) {
            $report = $s->report;
            if ($report && $report->isValidated()) {
                $status = 'validated';
            } elseif ($report && $report->isSubmitted()) {
                $status = 'to_validate';
            } elseif ($s->hasStarted()) {
                $status = 'awaiting';
            } else {
                $status = 'scheduled';
            }

            return (object) ['s' => $s, 'status' => $status];
        })->sort(function ($a, $b) use ($priority) {
            // Priorité d'abord ; au sein d'un statut, les validées les plus
            // récentes en tête, les autres par date de séance.
            $pa = $priority[$a->status];
            $pb = $priority[$b->status];
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            if ($a->status === 'validated') {
                return ($b->s->report?->validated_at?->getTimestamp() ?? 0) <=> ($a->s->report?->validated_at?->getTimestamp() ?? 0);
            }

            return ($a->s->starts_at?->getTimestamp() ?? 0) <=> ($b->s->starts_at?->getTimestamp() ?? 0);
        })->values();

        return view('admin.secretariat', [
            'events' => $events,
            'event_id' => $event ? (string) $event->id : '',
            'rows' => $rows,
            'stats' => [
                'to_validate' => $rows->where('status', 'to_validate')->count(),
                'awaiting' => $rows->where('status', 'awaiting')->count(),
                'scheduled' => $rows->where('status', 'scheduled')->count(),
                'validated' => $rows->where('status', 'validated')->count(),
                'total' => $rows->count(),
            ],
        ]);
    }

    /**
     * Onglet « Secrétariat assisté » (IA) : écran de génération de documents sur
     * mesure à partir d'un prompt ou d'un modèle joint.
     */
    public function assistIndex(Request $request): View
    {
        $docEditions = $request->user()->accessibleEditions('documents');
        $selectedId = $request->has('assist_event')
            ? (string) $request->query('assist_event', '')
            : $this->currentEditionId($docEditions);
        $current = $selectedId !== '' ? $docEditions->firstWhere('id', (int) $selectedId) : null;

        return view('admin.secretariat.assistant', [
            'docEditions' => $docEditions,
            'currentDocEditionId' => $current?->id,
            'presets' => self::PRESETS,
            'assistHistory' => array_slice((array) Settings::get('secretariat_ai_history', []), 0, 12),
        ]);
    }

    /**
     * Onglet « Documents » : documents logistiques standard (liste de présence,
     * feuille d'émargement, affiche directionnelle, ordre du jour, chevalets),
     * remplis à partir des données réelles de l'événement et imprimables - sans
     * IA, en renseignant quelques paramètres (cf. {@see printDocument()}).
     */
    public function documents(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'documents');
        $eventId = $request->has('doc_event')
            ? (string) $request->query('doc_event', '')
            : $this->currentEditionId($events);
        $event = $eventId !== '' ? $events->firstWhere('id', (int) $eventId) : null;

        $sessions = $event
            ? $event->sessions()->orderBy('starts_at')->orderBy('id')->get()
            : collect();

        return view('admin.secretariat.documents', [
            'events' => $events,
            'event' => $event,
            'sessions' => $sessions,
            'days' => $this->sessionDays($sessions),
            'rooms' => $sessions->map(fn (EventSession $s) => trim((string) $s->room))->filter()->unique()->sort()->values(),
            'types' => self::PRINT_TYPES,
            'header' => $event ? $this->docHeader($event) : $this->docHeaderDefaults(),
        ]);
    }

    /**
     * Enregistre l'en-tête des documents imprimables du secrétariat pour un
     * événement (surtitre/organisation, mention, logo, alignement). Conçu une
     * fois, il coiffe tous les documents logistiques.
     */
    public function saveDocHeader(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()->canOnEdition('documents', $event), 403);

        EventSettings::setMany($event, [
            'secretariat.doc_org' => trim((string) $request->input('doc_org')),
            'secretariat.doc_subtitle' => trim((string) $request->input('doc_subtitle')),
            'secretariat.doc_logo' => $request->boolean('doc_logo'),
            'secretariat.doc_align' => $request->input('doc_align') === 'center' ? 'center' : 'left',
        ]);

        return redirect()
            ->route('admin.secretariat.documents', ['doc_event' => $event->id])
            ->with('ukc_status', 'En-tête des documents enregistré.');
    }

    /**
     * En-tête résolu (réglages de l'événement + repli) pour coiffer les documents.
     *
     * @return array{org: string, subtitle: string, logo: bool, align: string}
     */
    protected function docHeader(Event $event): array
    {
        $org = trim((string) EventSettings::get($event, 'secretariat.doc_org', ''));

        return [
            'org' => $org !== '' ? $org : (string) config('app.name'),
            'subtitle' => trim((string) EventSettings::get($event, 'secretariat.doc_subtitle', '')),
            'logo' => (bool) EventSettings::get($event, 'secretariat.doc_logo', false),
            'align' => EventSettings::get($event, 'secretariat.doc_align', 'left') === 'center' ? 'center' : 'left',
        ];
    }

    /** @return array{org: string, subtitle: string, logo: bool, align: string} */
    protected function docHeaderDefaults(): array
    {
        return ['org' => (string) config('app.name'), 'subtitle' => '', 'logo' => false, 'align' => 'left'];
    }

    /**
     * Rendu imprimable d'un document logistique standard (nouvel onglet). Les
     * paramètres (séance, jour, portée…) arrivent en query ; les données viennent
     * de l'événement réel. La vue est autonome et déclenche l'impression.
     */
    public function printDocument(Request $request, Event $event, string $type): View
    {
        abort_unless($request->user()->canOnEdition('documents', $event), 403);
        abort_unless(isset(self::PRINT_TYPES[$type]), 404);

        return view('secretariat.print.'.$type, $this->printData($request, $event, $type) + [
            'event' => $event,
            'docTitle' => self::PRINT_TYPES[$type]['label'],
            'header' => $this->docHeader($event),
        ]);
    }

    /**
     * ⚠️ Ancienne page « Rapports de séance » par événement : fusionnée dans le
     * tableau de bord du secrétariat ({@see index()}), où lecture, amélioration
     * et validation se font sur place dans un panneau latéral. On ne garde ici
     * qu'une REDIRECTION pour les anciens liens, signets et e-mails. Deux écrans
     * pour le même objet, c'était deux endroits où chercher.
     */
    public function reports(Request $request): RedirectResponse
    {
        return redirect()->route('admin.secretariat');
    }

    /**
     * Relance le rapporteur d'une séance SILENCIEUSEMENT au clic : une
     * notification in-app à son compte (s'il en a un) et, selon sa préférence
     * e-mail, un e-mail rendu depuis le MODÈLE éditable `report_reminder`
     * (personnalisable parmi les modèles de mails). Aucun `mailto` à composer.
     *
     * ⚠️ Le type `report_reminder` est déclaré `hasMail` dans {@see NotificationKinds} :
     * la notification n'envoie donc PAS son e-mail générique - c'est le modèle
     * qui porte l'e-mail (pas de doublon).
     */
    public function remind(Request $request, EventSession $session): RedirectResponse
    {
        $event = $session->event;
        abort_unless($event && $request->user()->canOnEdition('editions_read', $event), 403);

        $email = filled($session->rapporteur_email) ? mb_strtolower(trim((string) $session->rapporteur_email)) : null;
        if ($email === null) {
            return back()->with('ukc_error', 'Aucun rapporteur désigné pour cette séance.');
        }

        $label = $session->label ?: 'une séance';
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        // Notification in-app (silencieuse) au compte du rapporteur, s'il existe.
        Notifier::account(
            $user,
            kind: 'report_reminder',
            title: 'Rapport de séance à rédiger',
            body: 'Merci de rédiger puis de soumettre le rapport de « '.$label.' ».',
            url: route('account.reports'),
            icon: 'sheet',
        );

        // E-mail de relance, rendu depuis le modèle éditable, envoyé en silence
        // (gaté par la préférence e-mail ; un destinataire sans compte le reçoit).
        if (Notifier::emailAllowed($user, 'report_reminder')) {
            $tpl = EmailTemplates::forReportReminder($session);
            $body = trim(($tpl['heading'] !== '' ? $tpl['heading']."\n\n" : '').$tpl['intro']);
            Mail::to($email)->send(new CampaignMail($tpl['subject'], $body));
        }

        return back()->with('ukc_status', 'Relance envoyée à '.($session->rapporteur_name ?: $email).'.');
    }

    public function generate(Request $request, GenerateReport $action): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'preset' => ['nullable', 'string'],
            'prompt' => ['nullable', 'string', 'max:2000'],
            'example' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx,xls,xlsx,csv', 'max:10240'],
            'format' => ['nullable', 'in:xlsx,csv,pdf,docx'],
            'dataset' => ['nullable', 'in:presence,none'],
            'aiProvider' => ['nullable', 'in:default,claude,openai,gemini,grok'],
        ]);

        $event = Event::findOrFail($data['event_id']);
        abort_unless($request->user()->canOnEdition('documents', $event), 403);

        $preset = self::PRESETS[$data['preset'] ?? ''] ?? null;
        $format = $data['format'] ?? ($preset['format'] ?? 'pdf');
        $dataset = $data['dataset'] ?? ($preset['dataset'] ?? 'none');
        $prompt = trim((string) ($data['prompt'] ?? '')) ?: (string) ($preset['prompt'] ?? '');

        $files = [];
        if ($request->hasFile('example')) {
            $file = $request->file('example');
            $files[] = ['mime' => (string) $file->getMimeType(), 'data' => base64_encode((string) $file->get())];
            if ($prompt === '') {
                $prompt = 'Reproduis la structure du modèle joint pour ce colloque.';
            }
        }

        if ($prompt === '' && $files === []) {
            return back()->withInput()->with('sec_error', 'Décrivez le document souhaité, choisissez un modèle prédéfini ou joignez un exemple.');
        }

        // Contexte du programme réel (salles, horaires, modérateurs/rapporteurs) pour
        // ancrer les documents logistiques (affiches, ordre du jour, chevalets).
        $brief = $prompt."\n\nContexte - programme réel de l’événement :\n".$this->programContext($event);

        // ⚠️ Sortir des 30 s par défaut de PHP : un document un peu long dépasse
        // la minute, et la requête mourait sans message.
        @set_time_limit(300);

        try {
            $doc = $action->handle($event, $brief, $files, $data['aiProvider'] ?? null, 'document');

            $entry = [
                'id' => (string) Str::uuid(),
                'event_id' => (string) $event->id,
                'title' => $doc['title'],
                'sections' => $doc['sections'],
                'format' => $format,
                'dataset' => $dataset,
                'preset' => $data['preset'] ?? null,
                'created_at' => now()->format('d/m/Y · H:i'),
            ];
            $this->pushHistory($entry);

            return back()->with('sec_doc', $entry);
        } catch (\Throwable $e) {
            return back()->withInput()->with('sec_error', $e->getMessage());
        }
    }

    /** Télécharge un document généré, dans son format d'origine. */
    public function download(Request $request, Event $event, string $id): Response
    {
        abort_unless($request->user()->canOnEdition('documents', $event), 403);

        $entry = collect((array) Settings::get('secretariat_ai_history', []))->firstWhere('id', $id);
        abort_if($entry === null, 404);

        $format = (string) ($entry['format'] ?? 'pdf');
        $base = 'secretariat-'.Str::slug((string) ($entry['title'] ?? 'document')).'-'.$id;

        $report = new EventReport($event);
        $datasets = ($entry['dataset'] ?? 'none') === 'presence'
            ? [['Présence', $report->participantHeaders(), $report->participantRows()]]
            : [];

        if ($format === 'xlsx') {
            $writer = new XlsxWriter;
            $narrative = [];
            foreach ((array) ($entry['sections'] ?? []) as $s) {
                $narrative[] = [(string) ($s['heading'] ?? ''), (string) ($s['body'] ?? '')];
            }
            $writer->sheet('Document', ['Section', 'Contenu'], $narrative ?: [['Document', (string) ($entry['title'] ?? '')]]);
            foreach ($datasets as [$name, $headers, $rows]) {
                $writer->sheet($name, $headers, $rows);
            }

            return $writer->download($base.'.xlsx');
        }

        if ($format === 'csv') {
            if ($datasets !== []) {
                [, $headers, $rows] = $datasets[0];
            } else {
                $headers = ['Section', 'Contenu'];
                $rows = array_map(fn ($s) => [(string) ($s['heading'] ?? ''), (string) ($s['body'] ?? '')], (array) ($entry['sections'] ?? []));
            }

            return response()->streamDownload(function () use ($headers, $rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers);
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $base.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // pdf / docx → page imprimable (réutilise la vue des rapports IA).
        $view = view('reports.ai', ['event' => $event, 'entry' => $entry, 'datasets' => $datasets]);

        if ($format === 'docx') {
            return response($view->render(), 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$base.'.doc"',
            ]);
        }

        return response($view->render());
    }

    /** Ajoute une entrée en tête de l'historique (max 30 conservées). */
    protected function pushHistory(array $entry): void
    {
        $history = (array) Settings::get('secretariat_ai_history', []);
        array_unshift($history, $entry);
        Settings::set('secretariat_ai_history', array_slice($history, 0, 30));
    }

    /**
     * Jours distincts (dates ISO) couverts par les séances.
     *
     * @param  Collection<int, EventSession>  $sessions
     * @return Collection<int, string>
     */
    protected function sessionDays(Collection $sessions): Collection
    {
        return $sessions
            ->map(fn (EventSession $s) => $s->starts_at?->toDateString())
            ->filter(fn (?string $d) => $d !== null)
            ->unique()->sort()->values();
    }

    /**
     * Données d'un document imprimable selon son type (sans IA, données réelles).
     *
     * @return array<string, mixed>
     */
    protected function printData(Request $request, Event $event, string $type): array
    {
        $sessions = $event->sessions()->orderBy('starts_at')->orderBy('id')->get();

        if ($type === 'presence' || $type === 'emargement') {
            // Liste vierge = aucune donnée pré-remplie, uniquement des lignes
            // numérotées à compléter à la main (nombre réglé par « rows »).
            $blank = $request->boolean('blank');
            if ($blank) {
                return [
                    'participants' => collect(),
                    'note' => trim((string) $request->query('note', '')),
                    'extraRows' => max(1, min(80, (int) $request->query('rows', '25'))),
                    'blank' => true,
                ];
            }

            return [
                'participants' => Participant::where('event_id', $event->id)
                    ->orderBy('last_name')->orderBy('first_name')->get(),
                'note' => trim((string) $request->query('note', '')),
                'extraRows' => max(0, min(40, (int) $request->query('extra', $type === 'emargement' ? '8' : '0'))),
                'blank' => false,
            ];
        }

        if ($type === 'ordre_jour') {
            $days = $this->sessionDays($sessions);
            $day = (string) $request->query('day', (string) $days->first());

            return [
                'day' => $day,
                'sessions' => $sessions
                    ->filter(fn (EventSession $s) => $s->starts_at && $s->starts_at->toDateString() === $day)
                    ->values(),
            ];
        }

        if ($type === 'affiche') {
            $day = trim((string) $request->query('day', ''));
            $scoped = $day !== ''
                ? $sessions->filter(fn (EventSession $s) => $s->starts_at && $s->starts_at->toDateString() === $day)
                : $sessions;

            return [
                'day' => $day,
                'byRoom' => $scoped->filter(fn (EventSession $s) => trim((string) $s->room) !== '')
                    ->groupBy(fn (EventSession $s) => trim((string) $s->room)),
            ];
        }

        // chevalets
        $who = in_array($request->query('who'), ['moderators', 'rapporteurs', 'both'], true)
            ? (string) $request->query('who')
            : 'both';

        $people = collect();
        foreach ($sessions as $s) {
            if ($who !== 'rapporteurs' && filled($s->moderator_name)) {
                $people->push(['name' => (string) $s->moderator_name, 'role' => 'Modérateur']);
            }
            if ($who !== 'moderators' && filled($s->rapporteur_name)) {
                $people->push(['name' => (string) $s->rapporteur_name, 'role' => 'Rapporteur']);
            }
        }

        return [
            'who' => $who,
            'people' => $people->unique(fn (array $p) => mb_strtolower($p['name']).'|'.$p['role'])->values(),
        ];
    }

    /** Résumé texte du programme réel de l'événement (pour ancrer les documents). */
    protected function programContext(Event $event): string
    {
        $lines = EventSession::where('event_id', $event->id)
            ->orderBy('starts_at')->orderBy('id')->get()
            ->map(function (EventSession $s) {
                $when = $s->starts_at ? $s->starts_at->translatedFormat('D j M · H:i') : '';
                $bits = array_filter([
                    $s->label,
                    // ⚠️ « Lieu », pas « Salle » : la colonne nomme déjà le lieu
                    // (« Salle 5 », mais aussi « Auditorium »), et le préfixe
                    // donnait « Salle : Salle 5 ». L'étiquette reste, elle, parce
                    // que cette ligne est une suite de champs nommés lue par le
                    // moteur de rédaction : sans elle, la valeur serait anonyme.
                    $s->room ? 'Lieu : '.$s->room : null,
                    $when ?: null,
                    $s->moderator_name ? 'Modérateur : '.$s->moderator_name : null,
                    $s->rapporteur_name ? 'Rapporteur : '.$s->rapporteur_name : null,
                ]);

                return '- '.implode(' | ', $bits);
            });

        return $lines->isEmpty() ? '(aucune séance programmée)' : $lines->implode("\n");
    }
}
