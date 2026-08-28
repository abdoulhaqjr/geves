<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ExtractEditionFromDocuments;
use App\AI\AiManager;
use App\Http\Controllers\Controller;
use App\Models\Colloque;
use App\Models\Edition;
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
        ];

        $committees = $edition
            ? $edition->committeeMembers()->orderBy('ordre')
                ->get(['role', 'person_name', 'person_title', 'institution', 'photo_url'])
                ->map(fn ($m) => $m->only(['role', 'person_name', 'person_title', 'institution', 'photo_url']))->all()
            : [];

        $arrays = [
            'fees' => $s['fees'] ?? [],
            'committees' => $committees,
            'partners' => $s['partners'] ?? [],
            'faq' => $s['faq'] ?? [],
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
        }

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

        $tracks = collect(preg_split('/\r\n|\r|\n/', (string) ($data['tracks'] ?? '')))
            ->map(fn ($t) => trim($t))->filter()->values()->all();

        $fees = collect($data['fees'] ?? [])
            ->filter(fn ($f) => trim((string) ($f['label'] ?? '')) !== '')
            ->map(fn ($f) => ['label' => $f['label'], 'amount' => (float) ($f['amount'] ?? 0)])->values()->all();

        $partners = collect($data['partners'] ?? [])
            ->filter(fn ($p) => trim((string) ($p['name'] ?? '')) !== '')
            ->map(fn ($p) => ['name' => $p['name'], 'url' => $p['url'] ?? null, 'logo_url' => $p['logo_url'] ?? null, 'level' => $p['level'] ?? null])->values()->all();

        $faq = collect($data['faq'] ?? [])
            ->filter(fn ($f) => trim((string) ($f['question'] ?? '')) !== '')
            ->map(fn ($f) => ['question' => $f['question'], 'answer' => $f['answer'] ?? ''])->values()->all();

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
                'tracks' => $tracks,
                'fees' => $fees,
                'max_communications' => $data['max_communications'],
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
                'practical' => array_merge($edition?->settings['practical'] ?? [], [
                    'venue' => ($data['practical_venue'] ?? null) ?: null,
                    'accommodation' => ($data['practical_accommodation'] ?? null) ?: null,
                    'transport' => ($data['practical_transport'] ?? null) ?: null,
                ]),
                'banner_enabled' => $request->boolean('banner_enabled'),
                'social' => array_filter([
                    'facebook' => ($data['social_facebook'] ?? null) ?: null,
                    'x' => ($data['social_x'] ?? null) ?: null,
                    'youtube' => ($data['social_youtube'] ?? null) ?: null,
                    'linkedin' => ($data['social_linkedin'] ?? null) ?: null,
                ]),
            ]),
        ];

        DB::transaction(function () use ($payload, $edition, $data) {
            $saved = $edition ? tap($edition)->update($payload) : Edition::create($payload);
            $this->syncCommittees($saved, $data['committees'] ?? []);
        });

        return redirect()->route('admin.editions.index')
            ->with('ukc_status', $edition ? 'Édition mise à jour.' : 'Édition créée.');
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
            in_array($field, ['status', 'slug', 'submission_open', 'submission_deadline', 'max_communications'], true),
            str_starts_with($field, 'fees') => 'submissions',
            str_starts_with($field, 'committees') => 'committees',
            str_starts_with($field, 'partners'), str_starts_with($field, 'faq') => 'partners',
            in_array($field, ['intro', 'tracks', 'hero_image', 'hero_video'], true),
            str_starts_with($field, 'contact'), str_starts_with($field, 'promo'), str_starts_with($field, 'practical') => 'content',
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
            'tracks' => ['nullable', 'string', 'max:3000'],
            'fees' => ['array'],
            'fees.*.label' => ['nullable', 'string', 'max:150'],
            'fees.*.amount' => ['nullable', 'numeric', 'min:0'],
            'committees' => ['array'],
            'committees.*.role' => ['nullable', 'in:organisation,scientifique,supervision'],
            'committees.*.person_name' => ['nullable', 'string', 'max:200'],
            'committees.*.person_title' => ['nullable', 'string', 'max:200'],
            'committees.*.institution' => ['nullable', 'string', 'max:255'],
            'committees.*.photo_url' => ['nullable', 'string', 'max:500'],
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
            'practical_venue' => ['nullable', 'string', 'max:2000'],
            'practical_accommodation' => ['nullable', 'string', 'max:2000'],
            'practical_transport' => ['nullable', 'string', 'max:2000'],
            'banner_enabled' => ['boolean'],
            'social_facebook' => ['nullable', 'url', 'max:300'],
            'social_x' => ['nullable', 'url', 'max:300'],
            'social_youtube' => ['nullable', 'url', 'max:300'],
            'social_linkedin' => ['nullable', 'url', 'max:300'],
        ];
    }
}
