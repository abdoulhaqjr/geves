<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExtractEventFromDocuments;
use App\Actions\SaveEvent;
use App\AI\AiManager;
use App\AI\Exceptions\AiUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEventRequest;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Participant;
use App\Models\Review;
use App\Models\User;
use App\Support\EventMeetingConfig;
use App\Support\EventPaymentConfig;
use App\Support\Locales;
use App\Support\ReviewerExpertise;
use App\Support\ReviewGrid;
use App\Support\StaffRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Création / événement d'un événement de colloque (contrôleur + Blade) : champs
 * cœur + réglages (introduction, axes, tarifs, comités, partenaires, FAQ,
 * apparence, contact, promo, infos pratiques). Onglets pilotés par Alpine ; les
 * listes répétables (tarifs/comités/partenaires/FAQ) aussi. Le paramétrage
 * assisté par IA analyse des documents puis pré-remplit le formulaire (redirige
 * avec les valeurs extraites, révisables avant enregistrement).
 */
class EventFormController extends Controller
{
    private const COMMITTEE_ROLES = [
        'organisation' => 'Comité d’organisation',
        'scientifique' => 'Comité scientifique',
        'supervision' => 'Comité de supervision',
    ];

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(Event $event): View
    {
        return $this->form($event);
    }

    public function store(SaveEventRequest $request, SaveEvent $action): RedirectResponse
    {
        $event = $action->handle($request, null);

        // L'administrateur de colloque qui crée l'événement en devient gestionnaire
        // (fonction pleine sur cet événement) afin de pouvoir la gérer. Le
        // super-admin voit déjà tout : pas besoin de l'affecter.
        $creator = $request->user();
        if ($creator instanceof User && ! $creator->isColloqueSuperAdmin()) {
            $already = DB::table('event_user')
                ->where('event_id', $event->id)->where('user_id', $creator->id)->exists();
            if (! $already) {
                $event->team()->attach($creator->id, ['role' => 'gerant']);
            }
        }

        return redirect()->route('admin.evenements.edit', $event)
            ->with('ukc_status', 'Événement créée.')
            ->with('edition_tab', $request->input('active_tab', 'general'));
    }

    public function update(SaveEventRequest $request, Event $event, SaveEvent $action): RedirectResponse
    {
        $saved = $action->handle($request, $event);

        return redirect()->route('admin.evenements.edit', $saved)
            ->with('ukc_status', 'Événement mise à jour.')
            ->with('edition_tab', $request->input('active_tab', 'general'));
    }

    /** Analyse les documents et pré-remplit les champs trouvés (révisable). */
    public function aiAssist(Request $request, ExtractEventFromDocuments $action): RedirectResponse
    {
        $request->validate([
            'aiFiles' => ['required', 'array', 'min:1', 'max:6'],
            'aiFiles.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);

        if (array_sum(array_map(fn ($file) => $file->getSize(), $request->file('aiFiles'))) > 20 * 1024 * 1024) {
            return back()->withInput()->withErrors(['aiFiles' => 'Les documents ne doivent pas dépasser 20 Mo au total.']);
        }

        $files = collect($request->file('aiFiles'))
            ->map(fn ($f) => ['mime' => (string) $f->getMimeType(), 'data' => base64_encode((string) $f->get())])
            ->all();

        // La lecture d'un PDF par un fournisseur distant peut dépasser les 30 s
        // de PHP-CGI. Cette limite est propre à l'analyse, pas aux autres pages.
        set_time_limit(300);

        try {
            $data = $action->handle($files);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['aiFiles' => $this->aiAssistErrorMessage($e)]);
        }

        [$extracted, $summary] = $this->normalizeExtracted($data);

        return back()
            ->with('ai_extracted', $extracted)
            ->with('ukc_status', $summary === []
                ? 'Aucune information exploitable n’a été extraite des documents.'
                : count($summary).' rubrique(s) pré-remplie(s) : '.implode(', ', $summary).'. Vérifiez puis enregistrez.');
    }

    /**
     * Variante AJAX du paramétrage assisté : renvoie les suggestions en JSON pour
     * que le panneau IA les affiche, puis les applique au formulaire côté client
     * (flux « Analyser → suggestions → Appliquer » du mockup). Accepte des fichiers
     * et/ou une consigne textuelle.
     */
    public function aiAssistJson(Request $request, ExtractEventFromDocuments $action): JsonResponse
    {
        $request->validate([
            'aiFiles' => ['nullable', 'array', 'max:6'],
            'aiFiles.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'aiPrompt' => ['nullable', 'string', 'max:4000'],
        ]);

        $uploads = $request->file('aiFiles') ?? [];
        if (array_sum(array_map(fn ($file) => $file->getSize(), $uploads)) > 20 * 1024 * 1024) {
            return response()->json(['ok' => false, 'message' => 'Les documents ne doivent pas dépasser 20 Mo au total.'], 422);
        }

        $files = collect($uploads)
            ->map(fn ($f) => ['mime' => (string) $f->getMimeType(), 'data' => base64_encode((string) $f->get())])
            ->all();

        $prompt = trim((string) $request->input('aiPrompt'));
        if ($files === [] && $prompt === '') {
            return response()->json(['ok' => false, 'message' => 'Fournissez un document ou une consigne.'], 422);
        }

        set_time_limit(300);

        try {
            [$extracted, $summary] = $this->normalizeExtracted($action->handle($files, $prompt));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => $this->aiAssistErrorMessage($e)], 503);
        }

        return response()->json([
            'ok' => true,
            'suggestions' => $extracted,
            'summary' => $summary,
            'message' => $summary === []
                ? 'Aucune information exploitable n’a été extraite.'
                : count($summary).' rubrique(s) proposée(s) : '.implode(', ', $summary).'.',
        ]);
    }

    // --- Rendu ----------------------------------------------------------------

    private function form(?Event $event): View
    {
        $init = $this->initValues($event);

        return view('admin.evenements.form', [
            'event' => $event,
            'init' => $init['scalars'],
            'fees' => $init['fees'],
            'committees' => $init['committees'],
            'partners' => $init['partners'],
            'faq' => $init['faq'],
            'comOrg' => $init['comOrg'],
            'comSci' => $init['comSci'],
            'comSup' => $init['comSup'],
            'keyDates' => $init['keyDates'],
            'axes' => $init['axes'],
            'why' => $init['why'],
            'infoCards' => $init['infoCards'],
            'stats' => $init['stats'],
            'languages' => $init['languages'],
            'refAxes' => $init['refAxes'],
            'seriesList' => EventSeries::orderBy('name')->get(),
            'committeeRoles' => self::COMMITTEE_ROLES,
            'aiAvailable' => app(AiManager::class)->available() !== [],
            'reviewCriteria' => ReviewGrid::criteria($event),
            'reviewScale' => ReviewGrid::SCALE,
            'reviewDueDays' => ReviewGrid::dueDays($event),
            'team' => $this->teamFor($event),
            'assignableUsers' => $event ? User::orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'teamRoles' => StaffRoles::teamRoles(),
        ]);
    }

    /**
     * Équipe staff de l'événement (une entrée par fonction) pour l'onglet « Équipe ».
     *
     * @return Collection<int, array{user_id:int, name:string, email:string, role:string, role_label:string, pill:string, caps:string}>
     */
    private function teamFor(?Event $event): Collection
    {
        if (! $event) {
            return collect();
        }

        return DB::table('event_user')
            ->join('users', 'users.id', '=', 'event_user.user_id')
            ->where('event_user.event_id', $event->id)
            ->orderBy('users.name')
            ->get(['users.id as user_id', 'users.name', 'users.email', 'event_user.role'])
            ->map(fn ($r) => [
                'user_id' => (int) $r->user_id,
                'name' => (string) $r->name,
                'email' => (string) $r->email,
                'role' => (string) $r->role,
                'role_label' => StaffRoles::label((string) $r->role),
                'pill' => StaffRoles::pill((string) $r->role),
                'caps' => collect(StaffRoles::permissionsFor((string) $r->role))
                    ->map(fn ($c) => StaffRoles::PERMISSIONS[$c] ?? $c)
                    ->implode(' - '),
            ])->values();
    }

    /** Ajoute un membre du staff à l'équipe de l'événement avec une fonction. */
    public function attachTeamMember(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string'],
        ]);

        abort_unless(StaffRoles::isTeamRole($data['role']), 422);

        $exists = DB::table('event_user')
            ->where('event_id', $event->id)->where('user_id', $data['user_id'])->where('role', $data['role'])
            ->exists();

        if (! $exists) {
            $event->team()->attach($data['user_id'], ['role' => $data['role']]);
        }

        return redirect()->route('admin.evenements.edit', $event)
            ->with('ukc_status', 'Membre ajouté à l’équipe.')
            ->with('edition_tab', 'team');
    }

    /** Retire une fonction d'un membre du staff sur l'événement. */
    public function detachTeamMember(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'role' => ['required', 'string'],
        ]);

        DB::table('event_user')
            ->where('event_id', $event->id)->where('user_id', $data['user_id'])->where('role', $data['role'])
            ->delete();

        return redirect()->route('admin.evenements.edit', $event)
            ->with('ukc_status', 'Membre retiré de l’équipe.')
            ->with('edition_tab', 'team');
    }

    /** Enregistre l'expertise (axes) des évaluateurs de l'événement (onglet Évaluateurs). */
    public function saveReviewerExpertise(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_ids' => ['array'],
            'reviewer_ids.*' => ['integer'],
            'expertise' => ['array'],
            'expertise.*' => ['array'],
            'expertise.*.*' => ['string', 'max:200'],
        ]);

        $reviewers = Participant::where('event_id', $event->id)
            ->where('role', 'reviewer')->get()->keyBy('id');

        foreach ($data['reviewer_ids'] ?? [] as $id) {
            $reviewer = $reviewers->get((int) $id);
            if (! $reviewer) {
                continue;
            }
            // On stocke le libellé CANONIQUE de l'événement : sans cela, l'expertise
            // conserve une vieille orthographe de l'axe et cesse de correspondre
            // dès que l'axe est renommé (cf. ReviewerExpertise::axisKey).
            $axes = ReviewerExpertise::axes($event);
            $tracks = collect((array) ($data['expertise'][$id] ?? []))
                ->map(function ($t) use ($axes) {
                    $key = ReviewerExpertise::axisKey((string) $t);

                    foreach ($axes as $axis) {
                        if (ReviewerExpertise::axisKey($axis) === $key) {
                            return $axis;
                        }
                    }

                    return trim((string) $t);
                })
                ->filter()->unique()->values()->all();
            $meta = $reviewer->meta ?? [];
            $meta['review_tracks'] = $tracks;
            $reviewer->update(['meta' => $meta]);
        }

        // L'expertise a son propre onglet dans le module Soumissions : on y revient.
        return redirect()->route('admin.submissions.reviewers', ['event_id' => $event->id])
            ->with('ukc_status', 'Expertise des évaluateurs mise à jour.');
    }

    /** Enregistre les traductions de contenu (i18n) d'une locale non par défaut. */
    public function saveTranslations(Request $request, Event $event): RedirectResponse
    {
        $locale = (string) $request->input('locale');
        abort_unless(Locales::supported($locale) && $locale !== config('app.fallback_locale'), 404);

        $data = $request->validate([
            'tr' => ['array'],
            'tr.*' => ['nullable', 'string', 'max:500'],
        ]);

        $translations = [];
        foreach (Event::TRANSLATABLE as $field) {
            $value = trim((string) ($data['tr'][$field] ?? ''));
            if ($value !== '') {
                $translations[$field] = $value;
            }
        }

        $settings = $event->settings ?? [];
        $i18n = is_array($settings['i18n'] ?? null) ? $settings['i18n'] : [];
        $i18n[$locale] = $translations;      // remplace la locale (les champs vidés disparaissent)
        $settings['i18n'] = array_filter($i18n, fn ($v) => $v !== []);
        $event->update(['settings' => $settings]);

        return redirect()->route('admin.evenements.edit', $event)
            ->with('ukc_status', 'Traductions enregistrées ('.mb_strtoupper($locale).').')
            // Les traductions vivent dans l'onglet Apparence : les deux faces de ce
            // que le visiteur voit du site public.
            ->with('edition_tab', 'appearance');
    }

    /** Enregistre la grille d'évaluation (critères notés) de l'événement. */
    public function saveReviewGrid(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'criteria' => ['array'],
            'criteria.*' => ['nullable', 'string', 'max:200'],
            'review_due_days' => ['nullable', 'integer', 'min:1', 'max:180'],
            'review_target' => ['nullable', 'integer', 'min:1', 'max:5'],
            'review_max_load' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $criteria = ReviewGrid::normalize(
            array_map(fn ($label) => ['label' => (string) $label], $data['criteria'] ?? [])
        );

        // On persiste tel quel ; la valeur par défaut n'est pas stockée (repli automatique).
        $settings = $event->settings ?? [];
        $settings['review_criteria'] = $criteria;
        // Délai d'évaluation : sert d'échéance à l'assignation (cf. AssignReviewer).
        // Vide = on retire la clé pour retomber sur Review::DEFAULT_DUE_DAYS.
        if (($data['review_due_days'] ?? null) !== null) {
            $settings['review_due_days'] = (int) $data['review_due_days'];
        } else {
            unset($settings['review_due_days']);
        }
        // Règles du moteur d'attribution (elles étaient des paramètres d'URL).
        foreach (['review_target', 'review_max_load'] as $key) {
            if (($data[$key] ?? null) !== null) {
                $settings[$key] = (int) $data[$key];
            } else {
                unset($settings[$key]);
            }
        }
        $event->update(['settings' => $settings]);

        return redirect()->route('admin.submissions.options', ['event_id' => $event->id])
            ->with('ukc_status', 'Règles d’évaluation mises à jour.');
    }

    /**
     * Passerelle de paiement PROPRE à l'événement (identifiants FedaPay).
     * Les champs laissés vides retombent sur la configuration globale de la
     * plateforme (cf. {@see EventPaymentConfig}).
     */
    public function savePayment(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'gateway' => ['nullable', 'in:manual,fedapay'],
            'fedapay' => ['array'],
            'fedapay.secret' => ['nullable', 'string', 'max:255'],
            'fedapay.mode' => ['nullable', 'in:test,live'],
            'fedapay.webhook_secret' => ['nullable', 'string', 'max:255'],
            'fedapay.public_key' => ['nullable', 'string', 'max:255'],
        ]);

        // On ne conserve que les valeurs renseignées (les vides = repli global).
        $clean = fn (array $a) => array_filter($a, fn ($v) => filled($v));
        $payment = array_filter([
            'gateway' => $data['gateway'] ?? null,
            'fedapay' => $clean($data['fedapay'] ?? []),
        ], fn ($v) => filled($v));

        $settings = $event->settings ?? [];
        if ($payment === []) {
            unset($settings['payment']);
        } else {
            $settings['payment'] = $payment;
        }
        $event->update(['settings' => $settings]);

        return redirect()->route('admin.evenements.edit', $event)
            ->with('ukc_status', 'Passerelle de paiement de l’événement mise à jour.')
            // Paiement et visioconférence partagent l'onglet « Intégrations » :
            // ce sont les deux branchements de l'événement sur un service extérieur.
            ->with('edition_tab', 'integrations');
    }

    /**
     * Réglages de visioconférence propres à l'événement : fournisseur, identifiants
     * du serveur, qualité vidéo.
     *
     * ⚠️ Même règle que le paiement : on ne conserve QUE les champs remplis, et un
     * champ vide retombe sur la configuration de la plateforme. Enregistrer une
     * chaîne vide figerait l'événement sur « rien », ce qui n'est pas « par défaut ».
     */
    public function saveMeetings(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['nullable', 'in:livekit,jitsi,custom'],
            'quality' => ['nullable', 'in:'.implode(',', EventMeetingConfig::QUALITIES)],
            'livekit' => ['array'],
            'livekit.url' => ['nullable', 'string', 'max:255'],
            'livekit.api_key' => ['nullable', 'string', 'max:255'],
            'livekit.api_secret' => ['nullable', 'string', 'max:255'],
            'jitsi' => ['array'],
            'jitsi.domain' => ['nullable', 'string', 'max:255'],
            'jitsi.app_id' => ['nullable', 'string', 'max:255'],
            'jitsi.app_secret' => ['nullable', 'string', 'max:255'],
            'jitsi.room_prefix' => ['nullable', 'string', 'max:60'],
        ]);

        $clean = fn (array $a) => array_filter($a, fn ($v) => filled($v));
        $meetings = array_filter([
            'provider' => $data['provider'] ?? null,
            'quality' => $data['quality'] ?? null,
            'livekit' => $clean($data['livekit'] ?? []),
            'jitsi' => $clean($data['jitsi'] ?? []),
        ], fn ($v) => filled($v));

        $settings = $event->settings ?? [];
        if ($meetings === []) {
            unset($settings['meetings']);
        } else {
            $settings['meetings'] = $meetings;
        }
        $event->update(['settings' => $settings]);

        return redirect()->route('admin.evenements.edit', $event)
            ->with('ukc_status', 'Visioconférence de l’événement mise à jour.')
            ->with('edition_tab', 'integrations');
    }

    /**
     * Valeurs initiales du formulaire : événement existant, écrasée par les
     * valeurs extraites par IA (flash) le cas échéant.
     *
     * @return array<string, mixed>
     */
    private function initValues(?Event $event): array
    {
        $s = $event?->settings ?? [];

        $scalars = [
            'event_series_id' => $event?->event_series_id,
            'title' => (string) ($event?->title ?? ''),
            'event_label' => (string) ($event?->event_label ?? ''),
            'subtitle' => (string) ($event?->subtitle ?? ''),
            'theme' => (string) ($event?->theme ?? ''),
            'year' => (int) ($event?->year ?: date('Y')),
            'date_start' => $event?->date_start?->format('Y-m-d') ?? '',
            'date_end' => $event?->date_end?->format('Y-m-d') ?? '',
            'location' => (string) ($event?->location ?? ''),
            'status' => (string) ($event?->status ?? 'draft'),
            'slug' => (string) ($event?->slug ?? ''),
            // ⚠️ Pas d'opérateur de sûreté à droite du `&&` : si l'événement est nul,
            // la gauche vaut déjà `false` et la droite n'est jamais évaluée.
            'submission_open' => (bool) ($event?->submission_open ?? false)
                && ($event->submission_deadline === null || $event->submission_deadline->isFuture()),
            'submission_deadline' => $event?->submission_deadline?->format('Y-m-d\TH:i') ?? '',
            'max_communications' => (int) ($s['max_communications'] ?? 3),
            'intro' => (string) ($s['intro'] ?? ''),
            'tracks' => implode("\n", $s['tracks'] ?? []),
            'hero_image' => (string) ($s['hero_image'] ?? ''),
            // La bannière vit en colonne, pas dans `settings` : c'est une image de
            // l'événement elle-même, reprise jusque dans la liste des événements passées.
            'banner_image' => (string) ($event?->banner_url ?? ''),
            'hero_video' => (string) ($s['hero_video'] ?? ''),
            'contact_email' => (string) ($s['contact']['email'] ?? ''),
            'contact_phone' => (string) ($s['contact']['phone'] ?? ''),
            'contact_address' => (string) ($s['contact']['address'] ?? ''),
            'promo_text' => (string) ($s['promo']['text'] ?? ''),
            'promo_url' => (string) ($s['promo']['url'] ?? ''),
            'promo_cta' => (string) ($s['promo']['cta'] ?? ''),
            'practical_venue' => (string) ($s['practical']['venue'] ?? ''),
            'practical_accommodation' => (string) ($s['practical']['accommodation'] ?? ''),
            'practical_transport' => (string) ($s['practical']['transport'] ?? ''),
            'banner_enabled' => (bool) ($s['banner_enabled'] ?? true),
            'social_facebook' => (string) ($s['social']['facebook'] ?? ''),
            'social_x' => (string) ($s['social']['x'] ?? ''),
            'social_youtube' => (string) ($s['social']['youtube'] ?? ''),
            'social_linkedin' => (string) ($s['social']['linkedin'] ?? ''),
            // Général (complément mockup)
            'short_name' => (string) ($s['short_name'] ?? ''),
            'short_desc' => (string) ($s['short_desc'] ?? ''),
            'format' => (string) ($s['format'] ?? 'Présentiel & hybride'),
            'main_language' => (string) ($s['main_language'] ?? 'Français'),
            // Soumissions : préfixe des références + attestations
            'ref_prefix_mode' => (string) ($s['ref_prefix_mode'] ?? 'global'),
            'ref_prefix' => (string) ($s['ref_prefix'] ?? 'R-'),
            'attestation_max' => (int) ($s['attestation_max'] ?? 5),
            'coauthors_max' => (int) ($s['coauthors_max'] ?? 8),
            'gen_participation' => (bool) ($s['gen_participation'] ?? true),
            'gen_communication' => (bool) ($s['gen_communication'] ?? true),
            // Apparence
            'hero_style' => (string) ($s['appearance']['hero_style'] ?? 'Image + dégradé'),
            'hero_align' => (string) ($s['appearance']['hero_align'] ?? 'Centre'),
            'color_primary' => (string) ($s['appearance']['color_primary'] ?? '#223ea4'),
            'color_secondary' => (string) ($s['appearance']['color_secondary'] ?? '#009933'),
            'hero_video_enabled' => (bool) ($s['appearance']['hero_video_enabled'] ?? false),
            'hero_video_muted' => (bool) ($s['appearance']['hero_video_muted'] ?? true),
            'stats_enabled' => (bool) ($s['appearance']['stats_enabled'] ?? false),
            // Options
            'opt_formation' => (bool) ($s['formation_enabled'] ?? false),
            'opt_show_public' => (bool) ($s['options']['show_public'] ?? true),
            'opt_hybrid' => (bool) ($s['options']['hybrid'] ?? true),
            'opt_livret' => (bool) ($s['options']['livret'] ?? true),
            'opt_badges' => (bool) ($s['options']['badges'] ?? true),
            'opt_coi_ignore_institution' => (bool) ($s['options']['coi_ignore_same_institution'] ?? true),
        ];

        $committees = $event
            ? $event->committeeMembers()->orderBy('ordre')
                ->get(['role', 'person_name', 'person_title', 'institution', 'email', 'phone', 'qualite', 'photo_url'])
                ->map(fn ($m) => $m->only(['role', 'person_name', 'person_title', 'institution', 'email', 'phone', 'qualite', 'photo_url']))->all()
            : [];

        $axes = collect($s['tracks'] ?? [])->map(fn ($t) => ['label' => (string) $t])->values()->all();

        // Langues : tolère une valeur héritée sous forme de chaîne (« Français & Anglais »).
        $langs = $s['languages'] ?? null;
        if (! is_array($langs)) {
            $langs = collect(preg_split('/[,&\/\n]+/', (string) $langs))->map(fn ($l) => trim($l))->filter()->values()->all();
        }
        $langs = $langs ?: ['Français'];

        $arrays = [
            'fees' => $s['fees'] ?? [],
            'committees' => $committees,
            'partners' => $s['partners'] ?? [],
            'faq' => $s['faq'] ?? [],
            'keyDates' => $s['key_dates'] ?? [],
            'axes' => $axes,
            'why' => $s['why'] ?? [],
            'infoCards' => $s['info_cards'] ?? [],
            'stats' => $s['appearance']['stats'] ?? [],
            'languages' => $langs,
            'refAxes' => $s['ref_prefix_axes'] ?? [],
        ];

        // Écrase avec les valeurs extraites par IA (session flash).
        if ($request = session('ai_extracted')) {
            foreach ($scalars as $k => $v) {
                if (array_key_exists($k, $request) && filled($request[$k])) {
                    $scalars[$k] = $request[$k];
                }
            }
            foreach (['fees', 'committees', 'partners', 'faq'] as $k) {
                if (! empty($request[$k])) {
                    $arrays[$k] = $request[$k];
                }
            }
            if (! empty($request['tracks'])) {
                $arrays['axes'] = collect($request['tracks'])->map(fn ($t) => ['label' => (string) $t])->values()->all();
            }
        }

        // Comités éclatés par rôle (mockup : une carte par comité).
        $byRole = collect($arrays['committees'])->groupBy(fn ($c) => $c['role'] ?? 'organisation');
        $arrays['comOrg'] = $byRole->get('organisation', collect())->values()->all();
        $arrays['comSci'] = $byRole->get('scientifique', collect())->values()->all();
        $arrays['comSup'] = $byRole->get('supervision', collect())->values()->all();

        return ['scalars' => $scalars] + $arrays;
    }

    /** Normalise les données extraites par IA en valeurs de formulaire + résumé. */
    private function normalizeExtracted(array $d): array
    {
        $out = [];
        $summary = [];

        $text = ['title' => 'Titre', 'event_label' => 'Libellé', 'subtitle' => 'Sous-titre',
            'theme' => 'Thème', 'location' => 'Lieu', 'intro' => 'Présentation'];
        foreach ($text as $k => $label) {
            if (filled($d[$k] ?? null)) {
                $out[$k] = (string) $d[$k];
                $summary[] = $label;
            }
        }
        if (! empty($d['year'])) {
            $out['year'] = (int) $d['year'];
            $summary[] = 'Année';
        }
        if (! empty($d['date_start'])) {
            $out['date_start'] = $d['date_start'];
            $summary[] = 'Dates';
        }
        if (! empty($d['date_end'])) {
            $out['date_end'] = $d['date_end'];
        }
        if (! empty($d['submission_deadline'])) {
            $out['submission_deadline'] = $d['submission_deadline'].'T23:59';
            $summary[] = 'Date limite';
        }
        if (! empty($d['tracks'])) {
            $out['tracks'] = $d['tracks'];
            $summary[] = 'Axes';
        }
        foreach (['fees' => 'Tarifs', 'committees' => 'Comités', 'partners' => 'Partenaires', 'faq' => 'FAQ'] as $k => $label) {
            if (! empty($d[$k])) {
                $out[$k] = $d[$k];
                $summary[] = $label;
            }
        }

        return [$out, $summary];
    }

    private function aiAssistErrorMessage(\Throwable $e): string
    {
        return $e instanceof AiUnavailableException
            ? $e->getMessage()
            : 'Analyse indisponible. Vérifiez la configuration des fournisseurs IA ou réessayez plus tard.';
    }
}
