<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExtractEditionFromDocuments;
use App\AI\AiManager;
use App\Http\Controllers\Controller;
use App\Models\Colloque;
use App\Models\Edition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    public function store(Request $request): RedirectResponse
    {
        return $this->persist($request, null);
    }

    public function update(Request $request, Edition $edition): RedirectResponse
    {
        return $this->persist($request, $edition);
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
        ]);
    }

    /**
     * Valeurs initiales du formulaire : édition existante, écrasée par les
     * valeurs extraites par IA (flash) le cas échéant.
     *
     * @return array{scalars: array<string, mixed>, fees: array, committees: array, partners: array, faq: array}
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

    // --- Persistance ----------------------------------------------------------

    private function persist(Request $request, ?Edition $edition): RedirectResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            $tab = $this->tabForField((string) array_key_first($validator->errors()->toArray()));

            return back()->withInput()->withErrors($validator)->with('edition_tab', $tab);
        }

        if (! $request->filled('colloque_id') && trim((string) $request->input('new_colloque')) === '') {
            return back()->withInput()->withErrors(['colloque_id' => 'Choisissez un colloque existant ou saisissez-en un nouveau.'])->with('edition_tab', 'general');
        }

        $data = $validator->validated();

        // Colloque de rattachement (existant ou nouveau).
        $colloqueId = $request->input('colloque_id') ?: null;
        if (trim((string) $request->input('new_colloque')) !== '') {
            $colloque = Colloque::create([
                'name' => $request->input('new_colloque'),
                'slug' => $this->uniqueColloqueSlug(Str::slug((string) $request->input('new_colloque'))),
            ]);
            $colloqueId = $colloque->id;
        }

        // Axes : liste répétable (mockup) → tableau de chaînes, avec repli sur l'ancien textarea.
        $tracks = collect($data['axes'] ?? [])
            ->map(fn ($a) => trim((string) ($a['label'] ?? '')))->filter()->values()->all();
        if (! $tracks && filled($data['tracks'] ?? null)) {
            $tracks = collect(preg_split('/\r\n|\r|\n/', (string) $data['tracks']))
                ->map(fn ($t) => trim($t))->filter()->values()->all();
        }

        $fees = collect($data['fees'] ?? [])
            ->filter(fn ($f) => trim((string) ($f['label'] ?? '')) !== '')
            ->map(fn ($f) => ['label' => $f['label'], 'amount' => (float) ($f['amount'] ?? 0)])->values()->all();

        $partners = collect($data['partners'] ?? [])
            ->filter(fn ($p) => trim((string) ($p['name'] ?? '')) !== '')
            ->map(fn ($p) => ['name' => $p['name'], 'url' => $p['url'] ?? null, 'logo_url' => $p['logo_url'] ?? null, 'level' => $p['level'] ?? null])->values()->all();

        $faq = collect($data['faq'] ?? [])
            ->filter(fn ($f) => trim((string) ($f['question'] ?? '')) !== '')
            ->map(fn ($f) => ['question' => $f['question'], 'answer' => $f['answer'] ?? ''])->values()->all();

        $keyDates = collect($data['keyDates'] ?? [])
            ->filter(fn ($d) => filled($d['date'] ?? null) || filled($d['label'] ?? null))
            ->map(fn ($d) => ['date' => (string) ($d['date'] ?? ''), 'label' => (string) ($d['label'] ?? '')])->values()->all();

        $programme = collect($data['programme'] ?? [])
            ->filter(fn ($p) => filled($p['title'] ?? null) || filled($p['time'] ?? null))
            ->map(fn ($p) => [
                'day' => (string) ($p['day'] ?? ''), 'time' => (string) ($p['time'] ?? ''), 'type' => (string) ($p['type'] ?? ''),
                'title' => (string) ($p['title'] ?? ''), 'speaker' => (string) ($p['speaker'] ?? ''), 'room' => (string) ($p['room'] ?? ''),
            ])->values()->all();

        $why = collect($data['why'] ?? [])
            ->filter(fn ($w) => filled($w['title'] ?? null))
            ->map(fn ($w) => ['title' => (string) $w['title'], 'text' => (string) ($w['text'] ?? '')])->values()->all();

        $infoCards = collect($data['infoCards'] ?? [])
            ->filter(fn ($c) => filled($c['title'] ?? null))
            ->map(fn ($c) => ['title' => (string) $c['title'], 'text' => (string) ($c['text'] ?? '')])->values()->all();

        $stats = collect($data['stats'] ?? [])
            ->filter(fn ($st) => filled($st['label'] ?? null) || filled($st['value'] ?? null))
            ->map(fn ($st) => ['label' => (string) ($st['label'] ?? ''), 'value' => (string) ($st['value'] ?? '')])->values()->all();

        $languages = collect($data['languages'] ?? [])->map(fn ($l) => trim((string) $l))->filter()->values()->all();
        $refAxes = collect($data['ref_axes'] ?? [])->map(fn ($r) => trim((string) $r))->values()->all();

        $payload = [
            'colloque_id' => $colloqueId,
            'title' => $data['title'],
            'edition_label' => $data['edition_label'] ?? '',
            'subtitle' => ($data['subtitle'] ?? null) ?: null,
            'theme' => ($data['theme'] ?? null) ?: null,
            'year' => ($data['year'] ?? null) ?: 0,
            'date_start' => ($data['date_start'] ?? null) ?: null,
            'date_end' => ($data['date_end'] ?? null) ?: null,
            'location' => ($data['location'] ?? null) ?: null,
            'status' => $data['status'],
            'slug' => $this->resolveSlug((string) ($data['slug'] ?? ''), $data['title'], $edition),
            'submission_open' => $request->boolean('submission_open'),
            'submission_deadline' => ($data['submission_deadline'] ?? null) ?: null,
            'settings' => array_merge($edition?->settings ?? [], [
                'intro' => ($data['intro'] ?? null) ?: null,
                'short_name' => ($data['short_name'] ?? null) ?: null,
                'short_desc' => ($data['short_desc'] ?? null) ?: null,
                'format' => ($data['format'] ?? null) ?: null,
                'main_language' => ($data['main_language'] ?? null) ?: null,
                'languages' => $languages,
                'tracks' => $tracks,
                'key_dates' => $keyDates,
                'programme' => $programme,
                'why' => $why,
                'info_cards' => $infoCards,
                'fees' => $fees,
                'max_communications' => $data['max_communications'],
                'ref_prefix_mode' => ($data['ref_prefix_mode'] ?? null) ?: 'global',
                'ref_prefix' => ($data['ref_prefix'] ?? null) ?: null,
                'ref_prefix_axes' => $refAxes,
                'attestation_max' => (int) ($data['attestation_max'] ?? 5),
                'coauthors_max' => (int) ($data['coauthors_max'] ?? 8),
                'gen_participation' => $request->boolean('gen_participation'),
                'gen_communication' => $request->boolean('gen_communication'),
                'partners' => $partners,
                'faq' => $faq,
                'hero_image' => ($data['hero_image'] ?? null) ?: null,
                'hero_video' => ($data['hero_video'] ?? null) ?: null,
                'contact' => [
                    'email' => ($data['contact_email'] ?? null) ?: null,
                    'phone' => ($data['contact_phone'] ?? null) ?: null,
                    'address' => ($data['contact_address'] ?? null) ?: null,
                ],
                'promo' => [
                    'text' => ($data['promo_text'] ?? null) ?: null,
                    'url' => ($data['promo_url'] ?? null) ?: null,
                    'cta' => ($data['promo_cta'] ?? null) ?: null,
                ],
                'appearance' => [
                    'hero_style' => ($data['hero_style'] ?? null) ?: null,
                    'hero_align' => ($data['hero_align'] ?? null) ?: null,
                    'color_primary' => ($data['color_primary'] ?? null) ?: null,
                    'color_secondary' => ($data['color_secondary'] ?? null) ?: null,
                    'hero_video_enabled' => $request->boolean('hero_video_enabled'),
                    'hero_video_muted' => $request->boolean('hero_video_muted'),
                    'news_enabled' => $request->boolean('news_enabled'),
                    'news_text' => ($data['news_text'] ?? null) ?: null,
                    'news_url' => ($data['news_url'] ?? null) ?: null,
                    'stats_enabled' => $request->boolean('stats_enabled'),
                    'stats' => $stats,
                ],
                'options' => [
                    'show_public' => $request->boolean('opt_show_public'),
                    'hybrid' => $request->boolean('opt_hybrid'),
                    'livret' => $request->boolean('opt_livret'),
                    'badges' => $request->boolean('opt_badges'),
                ],
                'banner_enabled' => $request->boolean('banner_enabled'),
                'social' => array_filter([
                    'facebook' => ($data['social_facebook'] ?? null) ?: null,
                    'x' => ($data['social_x'] ?? null) ?: null,
                    'youtube' => ($data['social_youtube'] ?? null) ?: null,
                    'linkedin' => ($data['social_linkedin'] ?? null) ?: null,
                ]),
            ]),
        ];

        // Comités : trois groupes distincts (mockup) → liste unique taguée par rôle.
        $committees = collect([
            'organisation' => $data['committees_org'] ?? [],
            'scientifique' => $data['committees_sci'] ?? [],
            'supervision' => $data['committees_sup'] ?? [],
        ])->flatMap(fn ($rows, $role) => collect($rows)->map(fn ($r) => array_merge((array) $r, ['role' => $role])))->all();

        // Rétro-compatibilité : ancien format « committees » plat (avec rôle).
        if ($committees === [] && ! empty($data['committees'])) {
            $committees = collect($data['committees'])->map(fn ($r) => (array) $r)->all();
        }

        $saved = DB::transaction(function () use ($payload, $edition, $committees) {
            $model = $edition ? tap($edition)->update($payload) : Edition::create($payload);
            $this->syncCommittees($model, $committees);

            return $model;
        });

        // Reste sur la fiche de l'édition, onglet courant conservé.
        return redirect()->route('admin.editions.edit', $saved)
            ->with('ukc_status', $edition ? 'Édition mise à jour.' : 'Édition créée.')
            ->with('edition_tab', $request->input('active_tab', 'general'));
    }

    /** Remplace les membres de comité de l'édition par ceux du formulaire. */
    private function syncCommittees(Edition $edition, array $committees): void
    {
        $edition->committeeMembers()->delete();

        $rows = collect($committees)->filter(fn ($c) => trim((string) ($c['person_name'] ?? '')) !== '')->values();

        foreach ($rows as $i => $c) {
            $edition->committeeMembers()->create([
                'role' => $c['role'] ?? 'organisation',
                'person_name' => $c['person_name'],
                'person_title' => $c['person_title'] ?? null,
                'institution' => $c['institution'] ?? null,
                'photo_url' => $c['photo_url'] ?? null,
                'ordre' => $i,
            ]);
        }
    }

    private function resolveSlug(string $slug, string $title, ?Edition $edition): string
    {
        $base = Str::slug($slug ?: $title) ?: 'edition';
        $result = $base;
        $i = 2;
        while (Edition::where('slug', $result)->when($edition, fn ($q) => $q->whereKeyNot($edition->id))->exists()) {
            $result = $base.'-'.$i++;
        }

        return $result;
    }

    private function uniqueColloqueSlug(string $base): string
    {
        $base = $base ?: 'colloque';
        $slug = $base;
        $i = 2;
        while (Colloque::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** Associe un champ à l'onglet du hub d'édition qui le contient. */
    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'keyDates'), str_starts_with($field, 'programme') => 'programme',
            in_array($field, ['submission_open', 'submission_deadline', 'max_communications', 'ref_prefix', 'ref_prefix_mode', 'attestation_max', 'coauthors_max', 'gen_participation', 'gen_communication'], true),
            str_starts_with($field, 'fees'), str_starts_with($field, 'ref_axes') => 'submissions',
            str_starts_with($field, 'committees') => 'committees',
            str_starts_with($field, 'partners') => 'partners',
            in_array($field, ['hero_style', 'hero_align', 'color_primary', 'color_secondary', 'hero_image', 'hero_video', 'hero_video_enabled', 'hero_video_muted', 'news_enabled', 'news_text', 'news_url', 'stats_enabled', 'banner_enabled', 'promo_text', 'promo_url', 'promo_cta'], true),
            str_starts_with($field, 'stats') => 'appearance',
            str_starts_with($field, 'opt_') => 'options',
            in_array($field, ['intro', 'tracks'], true),
            str_starts_with($field, 'axes'), str_starts_with($field, 'why'), str_starts_with($field, 'infoCards'),
            str_starts_with($field, 'faq'), str_starts_with($field, 'contact') => 'content',
            default => 'general',
        };
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'colloque_id' => ['nullable', 'exists:colloques,id'],
            'new_colloque' => ['nullable', 'string', 'max:200'],
            'title' => ['required', 'string', 'max:255'],
            'edition_label' => ['nullable', 'string', 'max:100'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'theme' => ['nullable', 'string', 'max:2000'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'location' => ['nullable', 'string', 'max:600'],
            'status' => ['required', 'in:draft,open,closed,archived'],
            'slug' => ['nullable', 'string', 'max:220'],
            'submission_open' => ['boolean'],
            'submission_deadline' => ['nullable', 'date'],
            'max_communications' => ['required', 'integer', 'min:1', 'max:10'],
            'intro' => ['nullable', 'string', 'max:5000'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'short_desc' => ['nullable', 'string', 'max:2000'],
            'format' => ['nullable', 'string', 'max:80'],
            'main_language' => ['nullable', 'string', 'max:60'],
            'languages' => ['array'],
            'languages.*' => ['nullable', 'string', 'max:60'],
            'tracks' => ['nullable', 'string', 'max:3000'],
            'axes' => ['array'],
            'axes.*.label' => ['nullable', 'string', 'max:200'],
            'keyDates' => ['array'],
            'keyDates.*.date' => ['nullable', 'string', 'max:40'],
            'keyDates.*.label' => ['nullable', 'string', 'max:200'],
            'programme' => ['array'],
            'programme.*.day' => ['nullable', 'string', 'max:60'],
            'programme.*.time' => ['nullable', 'string', 'max:60'],
            'programme.*.type' => ['nullable', 'string', 'max:60'],
            'programme.*.title' => ['nullable', 'string', 'max:300'],
            'programme.*.speaker' => ['nullable', 'string', 'max:200'],
            'programme.*.room' => ['nullable', 'string', 'max:100'],
            'why' => ['array'],
            'why.*.title' => ['nullable', 'string', 'max:200'],
            'why.*.text' => ['nullable', 'string', 'max:1000'],
            'infoCards' => ['array'],
            'infoCards.*.title' => ['nullable', 'string', 'max:120'],
            'infoCards.*.text' => ['nullable', 'string', 'max:1000'],
            'ref_prefix_mode' => ['nullable', 'in:global,axis'],
            'ref_prefix' => ['nullable', 'string', 'max:40'],
            'ref_axes' => ['array'],
            'ref_axes.*' => ['nullable', 'string', 'max:40'],
            'attestation_max' => ['nullable', 'integer', 'min:1', 'max:50'],
            'coauthors_max' => ['nullable', 'integer', 'min:1', 'max:30'],
            'gen_participation' => ['boolean'],
            'gen_communication' => ['boolean'],
            'hero_style' => ['nullable', 'string', 'max:60'],
            'hero_align' => ['nullable', 'string', 'max:20'],
            'color_primary' => ['nullable', 'string', 'max:20'],
            'color_secondary' => ['nullable', 'string', 'max:20'],
            'hero_video_enabled' => ['boolean'],
            'hero_video_muted' => ['boolean'],
            'news_enabled' => ['boolean'],
            'news_text' => ['nullable', 'string', 'max:300'],
            'news_url' => ['nullable', 'string', 'max:500'],
            'stats_enabled' => ['boolean'],
            'stats' => ['array'],
            'stats.*.label' => ['nullable', 'string', 'max:60'],
            'stats.*.value' => ['nullable', 'string', 'max:40'],
            'opt_show_public' => ['boolean'],
            'opt_hybrid' => ['boolean'],
            'opt_livret' => ['boolean'],
            'opt_badges' => ['boolean'],
            'fees' => ['array'],
            'fees.*.label' => ['nullable', 'string', 'max:150'],
            'fees.*.amount' => ['nullable', 'numeric', 'min:0'],
            'committees' => ['array'], // rétro-compat (ancien format plat)
            'committees.*.role' => ['nullable', 'in:organisation,scientifique,supervision'],
            'committees.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees.*.institution' => ['nullable', 'string', 'max:255'],
            'committees.*.photo_url' => ['nullable', 'string', 'max:500'],
            'committees_org' => ['array'],
            'committees_org.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees_org.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees_org.*.institution' => ['nullable', 'string', 'max:255'],
            'committees_sci' => ['array'],
            'committees_sci.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees_sci.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees_sci.*.institution' => ['nullable', 'string', 'max:255'],
            'committees_sup' => ['array'],
            'committees_sup.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees_sup.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees_sup.*.institution' => ['nullable', 'string', 'max:255'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:200'],
            'partners.*.url' => ['nullable', 'string', 'max:500'],
            'partners.*.logo_url' => ['nullable', 'string', 'max:500'],
            'partners.*.level' => ['nullable', 'string', 'max:40'],
            'faq' => ['array'],
            'faq.*.question' => ['nullable', 'string', 'max:300'],
            'faq.*.answer' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:500'],
            'hero_video' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'promo_text' => ['nullable', 'string', 'max:200'],
            'promo_url' => ['nullable', 'string', 'max:500'],
            'promo_cta' => ['nullable', 'string', 'max:60'],
            'banner_enabled' => ['boolean'],
            'social_facebook' => ['nullable', 'url', 'max:300'],
            'social_x' => ['nullable', 'url', 'max:300'],
            'social_youtube' => ['nullable', 'url', 'max:300'],
            'social_linkedin' => ['nullable', 'url', 'max:300'],
        ];
    }
}
