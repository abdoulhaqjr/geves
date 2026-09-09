<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExtractEditionFromDocuments;
use App\Actions\SaveEdition;
use App\AI\AiManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEditionRequest;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Review;
use App\Models\User;
use App\Support\Locales;
use App\Support\ReviewGrid;
use App\Support\StaffRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Création / édition d'une édition de colloque (contrôleur + Blade) : champs
 * cœur + réglages (introduction, axes, tarifs, comités, partenaires, FAQ,
 * apparence, contact, promo, infos pratiques). Onglets pilotés par Alpine ; les
 * listes répétables (tarifs/comités/partenaires/FAQ) aussi. Le paramétrage
 * assisté par IA analyse des documents puis pré-remplit le formulaire (redirige
 * avec les valeurs extraites, révisables avant enregistrement).
 */
class EditionFormController extends Controller
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

    public function edit(Edition $edition): View
    {
        return $this->form($edition);
    }

    public function store(SaveEditionRequest $request, SaveEdition $action): RedirectResponse
    {
        $edition = $action->handle($request, null);

        return redirect()->route('admin.editions.edit', $edition)
            ->with('ukc_status', 'Édition créée.')
            ->with('edition_tab', $request->input('active_tab', 'general'));
    }

    public function update(SaveEditionRequest $request, Edition $edition, SaveEdition $action): RedirectResponse
    {
        $saved = $action->handle($request, $edition);

        return redirect()->route('admin.editions.edit', $saved)
            ->with('ukc_status', 'Édition mise à jour.')
            ->with('edition_tab', $request->input('active_tab', 'general'));
    }

    /** Analyse les documents et pré-remplit les champs trouvés (révisable). */
    public function aiAssist(Request $request, ExtractEditionFromDocuments $action): RedirectResponse
    {
        $request->validate([
            'aiFiles' => ['required', 'array', 'min:1', 'max:6'],
            'aiFiles.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);

        $files = collect($request->file('aiFiles'))
            ->map(fn ($f) => ['mime' => (string) $f->getMimeType(), 'data' => base64_encode((string) $f->get())])
            ->all();

        try {
            $data = $action->handle($files);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['aiFiles' => 'Échec de l’analyse : '.$e->getMessage()]);
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
    public function aiAssistJson(Request $request, ExtractEditionFromDocuments $action): JsonResponse
    {
        $request->validate([
            'aiFiles' => ['nullable', 'array', 'max:6'],
            'aiFiles.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
            'aiPrompt' => ['nullable', 'string', 'max:4000'],
        ]);

        $files = collect($request->file('aiFiles') ?? [])
            ->map(fn ($f) => ['mime' => (string) $f->getMimeType(), 'data' => base64_encode((string) $f->get())])
            ->all();

        // La consigne textuelle est passée comme « document » texte à l'extracteur.
        if (trim((string) $request->input('aiPrompt')) !== '') {
            $files[] = ['mime' => 'text/plain', 'data' => base64_encode((string) $request->input('aiPrompt'))];
        }

        if ($files === []) {
            return response()->json(['ok' => false, 'message' => 'Fournissez un document ou une consigne.'], 422);
        }

        try {
            [$extracted, $summary] = $this->normalizeExtracted($action->handle($files));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Échec de l’analyse : '.$e->getMessage()], 500);
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

    private function form(?Edition $edition): View
    {
        $init = $this->initValues($edition);

        return view('admin.editions.form', [
            'edition' => $edition,
            'init' => $init['scalars'],
            'fees' => $init['fees'],
            'committees' => $init['committees'],
            'partners' => $init['partners'],
            'faq' => $init['faq'],
            'comOrg' => $init['comOrg'],
            'comSci' => $init['comSci'],
            'comSup' => $init['comSup'],
            'keyDates' => $init['keyDates'],
            'programme' => $init['programme'],
            'axes' => $init['axes'],
            'why' => $init['why'],
            'infoCards' => $init['infoCards'],
            'stats' => $init['stats'],
            'languages' => $init['languages'],
            'refAxes' => $init['refAxes'],
            'colloques' => Colloque::orderBy('name')->get(),
            'committeeRoles' => self::COMMITTEE_ROLES,
            'aiAvailable' => app(AiManager::class)->available() !== [],
            'reviewers' => $this->reviewersFor($edition),
            'reviewerAxes' => $this->reviewerAxes($edition),
            'reviewCriteria' => ReviewGrid::criteria($edition),
            'reviewScale' => ReviewGrid::SCALE,
            'team' => $this->teamFor($edition),
            'assignableUsers' => $edition ? User::orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'teamRoles' => StaffRoles::teamRoles(),
        ]);
    }

    /**
     * Équipe staff de l'édition (une entrée par fonction) pour l'onglet « Équipe ».
     *
     * @return Collection<int, array{user_id:int, name:string, email:string, role:string, role_label:string, pill:string}>
     */
    private function teamFor(?Edition $edition): Collection
    {
        if (! $edition) {
            return collect();
        }

        return DB::table('edition_user')
            ->join('users', 'users.id', '=', 'edition_user.user_id')
            ->where('edition_user.edition_id', $edition->id)
            ->orderBy('users.name')
            ->get(['users.id as user_id', 'users.name', 'users.email', 'edition_user.role'])
            ->map(fn ($r) => [
                'user_id' => (int) $r->user_id,
                'name' => (string) $r->name,
                'email' => (string) $r->email,
                'role' => (string) $r->role,
                'role_label' => StaffRoles::label((string) $r->role),
                'pill' => StaffRoles::pill((string) $r->role),
            ])->values();
    }

    /** Ajoute un membre du staff à l'équipe de l'édition avec une fonction. */
    public function attachTeamMember(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string'],
        ]);

        abort_unless(StaffRoles::isTeamRole($data['role']), 422);

        $exists = DB::table('edition_user')
            ->where('edition_id', $edition->id)->where('user_id', $data['user_id'])->where('role', $data['role'])
            ->exists();

        if (! $exists) {
            $edition->team()->attach($data['user_id'], ['role' => $data['role']]);
        }

        return redirect()->route('admin.editions.edit', $edition)
            ->with('ukc_status', 'Membre ajouté à l’équipe.')
            ->with('edition_tab', 'team');
    }

    /** Retire une fonction d'un membre du staff sur l'édition. */
    public function detachTeamMember(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'role' => ['required', 'string'],
        ]);

        DB::table('edition_user')
            ->where('edition_id', $edition->id)->where('user_id', $data['user_id'])->where('role', $data['role'])
            ->delete();

        return redirect()->route('admin.editions.edit', $edition)
            ->with('ukc_status', 'Membre retiré de l’équipe.')
            ->with('edition_tab', 'team');
    }

    /**
     * Relecteurs de l'édition avec expertise déclarée et charge courante
     * (pour l'onglet « Relecteurs » de la fiche).
     *
     * @return Collection<int, array{id:int, name:string, institution:string, tracks:array<int, string>, load:int}>
     */
    private function reviewersFor(?Edition $edition): Collection
    {
        if (! $edition) {
            return collect();
        }

        $reviewers = Participant::where('edition_id', $edition->id)
            ->where('role', 'reviewer')->orderBy('last_name')->get();

        $load = Review::query()
            ->whereIn('reviewer_id', $reviewers->pluck('id'))
            ->whereHas('submission', fn ($q) => $q->where('edition_id', $edition->id))
            ->selectRaw('reviewer_id, count(*) as c')->groupBy('reviewer_id')->pluck('c', 'reviewer_id');

        return $reviewers->map(fn (Participant $r) => [
            'id' => $r->id,
            'name' => $r->full_name,
            'institution' => (string) ($r->institution ?? ''),
            'tracks' => collect($r->meta['review_tracks'] ?? [])
                ->filter()->map(fn ($t): string => trim((string) $t))->values()->all(),
            'load' => (int) ($load[$r->id] ?? 0),
        ])->values();
    }

    /**
     * Axes thématiques de l'édition (réglages), pour cocher l'expertise.
     *
     * @return list<string>
     */
    private function reviewerAxes(?Edition $edition): array
    {
        return collect($edition?->settings['tracks'] ?? [])
            ->map(fn ($t): string => trim((string) $t))
            ->filter()->unique()->values()->all();
    }

    /** Enregistre l'expertise (axes) des relecteurs de l'édition (onglet Relecteurs). */
    public function saveReviewerExpertise(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_ids' => ['array'],
            'reviewer_ids.*' => ['integer'],
            'expertise' => ['array'],
            'expertise.*' => ['array'],
            'expertise.*.*' => ['string', 'max:200'],
        ]);

        $reviewers = Participant::where('edition_id', $edition->id)
            ->where('role', 'reviewer')->get()->keyBy('id');

        foreach ($data['reviewer_ids'] ?? [] as $id) {
            $reviewer = $reviewers->get((int) $id);
            if (! $reviewer) {
                continue;
            }
            $tracks = array_values(array_unique(array_filter(
                array_map(fn ($t) => trim((string) $t), (array) ($data['expertise'][$id] ?? []))
            )));
            $meta = $reviewer->meta ?? [];
            $meta['review_tracks'] = $tracks;
            $reviewer->update(['meta' => $meta]);
        }

        return redirect()->route('admin.editions.edit', $edition)
            ->with('ukc_status', 'Expertise des relecteurs mise à jour.')
            ->with('edition_tab', 'reviewers');
    }

    /** Enregistre les traductions de contenu (i18n) d'une locale non par défaut. */
    public function saveTranslations(Request $request, Edition $edition): RedirectResponse
    {
        $locale = (string) $request->input('locale');
        abort_unless(Locales::supported($locale) && $locale !== config('app.fallback_locale'), 404);

        $data = $request->validate([
            'tr' => ['array'],
            'tr.*' => ['nullable', 'string', 'max:500'],
        ]);

        $translations = [];
        foreach (Edition::TRANSLATABLE as $field) {
            $value = trim((string) ($data['tr'][$field] ?? ''));
            if ($value !== '') {
                $translations[$field] = $value;
            }
        }

        $settings = $edition->settings ?? [];
        $i18n = is_array($settings['i18n'] ?? null) ? $settings['i18n'] : [];
        $i18n[$locale] = $translations;      // remplace la locale (les champs vidés disparaissent)
        $settings['i18n'] = array_filter($i18n, fn ($v) => $v !== []);
        $edition->update(['settings' => $settings]);

        return redirect()->route('admin.editions.edit', $edition)
            ->with('ukc_status', 'Traductions enregistrées ('.mb_strtoupper($locale).').')
            ->with('edition_tab', 'appearance');
    }

    /** Enregistre la grille d'évaluation (critères notés) de l'édition. */
    public function saveReviewGrid(Request $request, Edition $edition): RedirectResponse
    {
        $data = $request->validate([
            'criteria' => ['array'],
            'criteria.*' => ['nullable', 'string', 'max:200'],
        ]);

        $criteria = ReviewGrid::normalize(
            array_map(fn ($label) => ['label' => (string) $label], $data['criteria'] ?? [])
        );

        // On persiste tel quel ; la valeur par défaut n'est pas stockée (repli automatique).
        $settings = $edition->settings ?? [];
        $settings['review_criteria'] = $criteria;
        $edition->update(['settings' => $settings]);

        return redirect()->route('admin.editions.edit', $edition)
            ->with('ukc_status', 'Grille d’évaluation mise à jour.')
            ->with('edition_tab', 'reviewers');
    }

    /**
     * Valeurs initiales du formulaire : édition existante, écrasée par les
     * valeurs extraites par IA (flash) le cas échéant.
     *
     * @return array<string, mixed>
     */
    private function initValues(?Edition $edition): array
    {
        $s = $edition?->settings ?? [];

        $scalars = [
            'colloque_id' => $edition?->colloque_id,
            'title' => (string) ($edition?->title ?? ''),
            'edition_label' => (string) ($edition?->edition_label ?? ''),
            'subtitle' => (string) ($edition?->subtitle ?? ''),
            'theme' => (string) ($edition?->theme ?? ''),
            'year' => (int) ($edition?->year ?: date('Y')),
            'date_start' => $edition?->date_start?->format('Y-m-d') ?? '',
            'date_end' => $edition?->date_end?->format('Y-m-d') ?? '',
            'location' => (string) ($edition?->location ?? ''),
            'status' => (string) ($edition?->status ?? 'draft'),
            'slug' => (string) ($edition?->slug ?? ''),
            'submission_open' => (bool) ($edition?->submission_open ?? false),
            'submission_deadline' => $edition?->submission_deadline?->format('Y-m-d\TH:i') ?? '',
            'max_communications' => (int) ($s['max_communications'] ?? 3),
            'intro' => (string) ($s['intro'] ?? ''),
            'tracks' => implode("\n", $s['tracks'] ?? []),
            'hero_image' => (string) ($s['hero_image'] ?? ''),
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
            'ref_prefix' => (string) ($s['ref_prefix'] ?? 'UKC-S-'),
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
            'news_enabled' => (bool) ($s['appearance']['news_enabled'] ?? false),
            'news_text' => (string) ($s['appearance']['news_text'] ?? ''),
            'news_url' => (string) ($s['appearance']['news_url'] ?? ''),
            'stats_enabled' => (bool) ($s['appearance']['stats_enabled'] ?? false),
            // Options
            'opt_show_public' => (bool) ($s['options']['show_public'] ?? true),
            'opt_hybrid' => (bool) ($s['options']['hybrid'] ?? true),
            'opt_livret' => (bool) ($s['options']['livret'] ?? true),
            'opt_badges' => (bool) ($s['options']['badges'] ?? true),
        ];

        $committees = $edition
            ? $edition->committeeMembers()->orderBy('ordre')
                ->get(['role', 'person_name', 'person_title', 'institution', 'photo_url'])
                ->map(fn ($m) => $m->only(['role', 'person_name', 'person_title', 'institution', 'photo_url']))->all()
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
            'programme' => $s['programme'] ?? [],
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

        $text = ['title' => 'Titre', 'edition_label' => 'Libellé', 'subtitle' => 'Sous-titre',
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
            $out['tracks'] = implode("\n", $d['tracks']);
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
}
