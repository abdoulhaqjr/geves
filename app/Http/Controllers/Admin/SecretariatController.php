<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateReport;
use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSession;
use App\Support\EventReport;
use App\Support\Settings;
use App\Support\XlsxWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function index(Request $request): View
    {
        $user = $request->user();
        $events = $user->accessibleEditions('editions_read');
        $editionIds = $events->pluck('id');

        /** @var Collection<int, EventSession> $sessions */
        $sessions = $editionIds->isEmpty()
            ? collect()
            : EventSession::whereIn('event_id', $editionIds)
                ->whereNotNull('rapporteur_email')->where('rapporteur_email', '!=', '')
                ->with(['event.series', 'atelier', 'report'])
                ->orderBy('starts_at')->orderBy('id')->get();

        $toValidate = $sessions->filter(fn (EventSession $s) => $s->report && $s->report->isSubmitted());
        $awaiting = $sessions->filter(fn (EventSession $s) => $s->hasStarted()
            && (! $s->report || $s->report->status === 'draft'));
        $validated = $sessions->filter(fn (EventSession $s) => $s->report && $s->report->isValidated());

        // Secrétariat assisté : événements où l'utilisateur peut produire des documents.
        $docEditions = $user->accessibleEditions('documents');
        $currentDoc = $docEditions->firstWhere('status', 'open') ?? $docEditions->first();

        return view('admin.secretariat', [
            'events' => $events,
            'toValidate' => $toValidate->values(),
            'awaiting' => $awaiting->values(),
            'validated' => $validated->sortByDesc(fn (EventSession $s) => $s->report?->validated_at)->take(6)->values(),
            'stats' => [
                'to_validate' => $toValidate->count(),
                'validated' => $validated->count(),
                'awaiting' => $awaiting->count(),
            ],
            'docEditions' => $docEditions,
            'currentDocEditionId' => $currentDoc?->id,
            'presets' => self::PRESETS,
            'assistHistory' => array_slice((array) Settings::get('secretariat_ai_history', []), 0, 12),
        ]);
    }

    /** Génère un document du secrétariat via IA (prompt ou modèle joint). */
    /**
     * Rapports de séance d'un événement : lecture et amélioration par le
     * secrétariat, avant validation.
     *
     * ⚠️ Les rapports relèvent du secrétariat et de lui seul - le module
     * Programme n'en porte plus de copie : deux écrans pour le même objet, c'est
     * deux endroits où chercher et un risque d'états divergents.
     */
    public function reports(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'editions_read');
        $editionId = $request->has('event_id')
            ? (string) $request->query('event_id', '')
            : $this->currentEditionId($events);
        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;

        return view('admin.secretariat.reports', [
            'events' => $events,
            'event_id' => $event ? (string) $event->id : '',
            'sessions' => $event
                ? $event->sessions()->with(['atelier', 'report'])->orderBy('starts_at')->orderBy('id')->get()
                : collect(),
        ]);
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

    /** Résumé texte du programme réel de l'événement (pour ancrer les documents). */
    protected function programContext(Event $event): string
    {
        $lines = EventSession::where('event_id', $event->id)
            ->orderBy('starts_at')->orderBy('id')->get()
            ->map(function (EventSession $s) {
                $when = $s->starts_at ? $s->starts_at->translatedFormat('D j M · H:i') : '';
                $bits = array_filter([
                    $s->label,
                    $s->room ? 'Salle : '.$s->room : null,
                    $when ?: null,
                    $s->moderator_name ? 'Modérateur : '.$s->moderator_name : null,
                    $s->rapporteur_name ? 'Rapporteur : '.$s->rapporteur_name : null,
                ]);

                return '- '.implode(' | ', $bits);
            });

        return $lines->isEmpty() ? '(aucune séance programmée)' : $lines->implode("\n");
    }
}
