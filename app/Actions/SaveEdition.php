<?php

namespace App\Actions;

use App\Http\Requests\Admin\SaveEditionRequest;
use App\Models\Colloque;
use App\Models\Edition;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crée ou met à jour une édition depuis la fiche back-office : rattachement au
 * colloque (existant ou nouveau), composition du bloc `settings` (contenu public,
 * tarifs, apparence, options…), synchronisation des comités et slug unique, le
 * tout de façon atomique. La validation est faite en amont par
 * {@see SaveEditionRequest}.
 */
class SaveEdition
{
    public function handle(SaveEditionRequest $request, ?Edition $edition): Edition
    {
        $data = $request->validated();

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
            ->filter(fn ($f) => is_array($f) && trim((string) ($f['label'] ?? '')) !== '')
            ->map(fn ($f) => [
                'label' => (string) $f['label'],
                'amount' => (float) ($f['amount'] ?? 0),
                // Grades couverts : c'est ce rattachement qui permet de retrouver
                // le tarif d'un participant (le libellé est rédigé librement).
                'grades' => collect(is_array($f['grades'] ?? null) ? $f['grades'] : [])
                    ->map(fn ($g) => trim((string) $g))->filter()->unique()->values()->all(),
                'default' => (bool) ($f['default'] ?? false),
            ])->values()->all();

        $partners = collect($data['partners'] ?? [])
            ->filter(fn ($p) => trim((string) ($p['name'] ?? '')) !== '')
            ->map(fn ($p) => ['name' => $p['name'], 'url' => $p['url'] ?? null, 'logo_url' => $p['logo_url'] ?? null, 'level' => $p['level'] ?? null])->values()->all();

        $faq = collect($data['faq'] ?? [])
            ->filter(fn ($f) => trim((string) ($f['question'] ?? '')) !== '')
            ->map(fn ($f) => ['question' => $f['question'], 'answer' => $f['answer'] ?? ''])->values()->all();

        $keyDates = collect($data['keyDates'] ?? [])
            ->filter(fn ($d) => filled($d['date'] ?? null) || filled($d['label'] ?? null))
            ->map(fn ($d) => ['date' => (string) ($d['date'] ?? ''), 'label' => (string) ($d['label'] ?? '')])->values()->all();

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

        $existingSettings = $edition ? ($edition->settings ?? []) : [];

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
            'settings' => array_merge($existingSettings, [
                'intro' => ($data['intro'] ?? null) ?: null,
                'short_name' => ($data['short_name'] ?? null) ?: null,
                'short_desc' => ($data['short_desc'] ?? null) ?: null,
                'format' => ($data['format'] ?? null) ?: null,
                'main_language' => ($data['main_language'] ?? null) ?: null,
                'languages' => $languages,
                'tracks' => $tracks,
                'key_dates' => $keyDates,
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
                    'coi_ignore_same_institution' => $request->boolean('opt_coi_ignore_institution'),
                ],
                'banner_enabled' => $request->boolean('banner_enabled'),
                'formation_enabled' => $request->boolean('opt_formation'),
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

        return DB::transaction(function () use ($payload, $edition, $committees): Edition {
            $model = $edition ? tap($edition)->update($payload) : Edition::create($payload);
            $this->syncCommittees($model, $committees);

            return $model;
        });
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
}
