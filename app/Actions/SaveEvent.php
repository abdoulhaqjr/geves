<?php

namespace App\Actions;

use App\Enums\EventType;
use App\Http\Requests\Admin\SaveEventRequest;
use App\Models\Event;
use App\Models\EventSeries;
use App\Support\EventArtwork;
use App\Support\EventNameFormat;
use App\Support\EventStorage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Crée ou met à jour un événement depuis la fiche back-office : rattachement au
 * colloque (existant ou nouveau), composition du bloc `settings` (contenu public,
 * tarifs, apparence, options…), synchronisation des comités et slug unique, le
 * tout de façon atomique. La validation est faite en amont par
 * {@see SaveEventRequest}.
 */
class SaveEvent
{
    public function handle(SaveEventRequest $request, ?Event $event): Event
    {
        $data = $request->validated();

        // Série de rattachement (existant ou nouveau) et son TYPE (colloque,
        // séminaire, journées scientifiques…). Le type est porté par la série.
        $seriesType = $request->input('series_type') ?: EventType::Colloque->value;
        $colloqueId = $request->input('event_series_id') ?: null;
        if (trim((string) $request->input('new_colloque')) !== '') {
            $series = EventSeries::create([
                'name' => $request->input('new_colloque'),
                'slug' => $this->uniqueColloqueSlug(Str::slug((string) $request->input('new_colloque'))),
                'type' => $seriesType,
            ]);
            $colloqueId = $series->id;
        } elseif ($colloqueId && $request->filled('series_type')) {
            // Série existante : on autorise la mise à jour de son type depuis la fiche.
            EventSeries::whereKey($colloqueId)->update(['type' => $seriesType]);
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

        // ⚠️ **Le slug est résolu AVANT l'habillage**, car c'est lui qui nomme le
        // dossier des fichiers ({@see EventStorage}). À la création, la ligne
        // n'existe pas encore au moment du téléversement : sans cette résolution
        // en amont, il n'y aurait rien pour nommer le dossier. `resolveSlug()`
        // est déterministe - l'appeler ici ou plus bas donne le même résultat.
        $slug = $this->resolveSlug((string) ($data['slug'] ?? ''), $data['title'], $event);
        $dossier = EventStorage::artwork($slug, $data['year'] ?? null);

        // Logo d'un partenaire : téléversé OU collé, par le même chemin que
        // l'habillage de l'événement - le fichier l'emporte sur l'URL.
        // ⚠️ Chaque ligne porte l'URL qu'elle avait, et on ne rapproche JAMAIS
        // l'ancienne liste de la nouvelle par l'index : le répéteur se réordonne
        // et se vide, l'index ne désigne plus la même entrée. Le ménage des
        // fichiers devenus orphelins se fait après coup, sur les deux listes
        // entières.
        $logosAvant = collect($event ? ($event->settings['partners'] ?? []) : [])
            ->map(fn ($p) => (string) ($p['logo_url'] ?? ''))->all();

        $partners = collect($data['partners'] ?? [])
            ->filter(fn ($p) => trim((string) ($p['name'] ?? '')) !== '')
            ->map(fn ($p, $i) => [
                'name' => $p['name'],
                'url' => $p['url'] ?? null,
                'logo_url' => EventArtwork::resolve(
                    $request->file('partners.'.$i.'.logo_file'),
                    (string) ($p['logo_url'] ?? ''),
                    null,
                    dir: $dossier,
                ),
                'level' => $p['level'] ?? null,
            ])->values()->all();

        EventArtwork::forgetMissing($logosAvant, array_column($partners, 'logo_url'));

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

        $existingSettings = $event ? ($event->settings ?? []) : [];

        // Habillage public : téléversement ou URL, pour le bandeau comme pour la
        // bannière. Résolu AVANT le payload, car l'ancien fichier est supprimé au
        // passage (cf. EventArtwork).
        $heroImage = EventArtwork::resolve(
            $request->file('hero_image_file'),
            $data['hero_image'] ?? null,
            $existingSettings['hero_image'] ?? null,
            $request->boolean('hero_image_remove'),
            $dossier,
        );

        $heroVideo = EventArtwork::resolve(
            $request->file('hero_video_file'),
            $data['hero_video'] ?? null,
            $existingSettings['hero_video'] ?? null,
            $request->boolean('hero_video_remove'),
            $dossier,
        );

        $bannerUrl = EventArtwork::resolve(
            $request->file('banner_image_file'),
            $data['banner_image'] ?? null,
            $event?->banner_url,
            $request->boolean('banner_image_remove'),
            $dossier,
        );

        $submissionDeadline = ($data['submission_deadline'] ?? null) ?: null;
        $submissionOpen = $request->boolean('submission_open')
            && ($submissionDeadline === null || Carbon::parse($submissionDeadline)->isFuture());

        $payload = [
            'banner_url' => $bannerUrl,
            'event_series_id' => $colloqueId,
            'title' => $data['title'],
            'event_label' => $data['event_label'] ?? '',
            'subtitle' => ($data['subtitle'] ?? null) ?: null,
            'theme' => ($data['theme'] ?? null) ?: null,
            'year' => ($data['year'] ?? null) ?: 0,
            'date_start' => ($data['date_start'] ?? null) ?: null,
            'date_end' => ($data['date_end'] ?? null) ?: null,
            'location' => ($data['location'] ?? null) ?: null,
            'status' => $data['status'],
            // ⚠️ Le slug est résolu PLUS HAUT : c'est lui qui nomme le dossier des
            // fichiers de l'événement (cf. EventStorage), et le téléversement de
            // l'habillage le précède dans cette méthode.
            'slug' => $slug,
            'submission_open' => $submissionOpen,
            'submission_deadline' => $submissionDeadline,
            'settings' => array_merge($existingSettings, [
                'intro' => ($data['intro'] ?? null) ?: null,
                'short_name' => ($data['short_name'] ?? null) ?: null,
                'short_desc' => ($data['short_desc'] ?? null) ?: null,
                'format' => ($data['format'] ?? null) ?: null,
                'main_language' => ($data['main_language'] ?? null) ?: null,
                'name_order' => $data['name_order'] ?? $existingSettings['name_order'] ?? EventNameFormat::FIRST_LAST,
                'languages' => $languages,
                'tracks' => $tracks,
                'key_dates' => $keyDates,
                'why' => $why,
                'info_cards' => $infoCards,
                'fees' => $fees,
                'max_communications' => $data['max_communications'],
                // ⚠️ La case porte un `<input type="hidden" value="0">` devant
                // elle dans le formulaire : sans lui, décocher n'enverrait RIEN
                // et `boolean()` rendrait false pour la mauvaise raison - on ne
                // saurait pas distinguer « fermé » de « champ absent ».
                'registration_open' => $request->boolean('registration_open'),
                'registration_deadline' => ($data['registration_deadline'] ?? null) ?: null,
                // ⚠️ Même champ caché devant la case : décocher doit envoyer 0,
                // sinon on ne distingue pas « fermé » de « champ absent ».
                'attendee_payment_open' => $request->boolean('attendee_payment_open'),
                'ref_prefix_mode' => ($data['ref_prefix_mode'] ?? null) ?: 'global',
                'ref_prefix' => ($data['ref_prefix'] ?? null) ?: null,
                'ref_prefix_axes' => $refAxes,
                'attestation_max' => (int) ($data['attestation_max'] ?? 5),
                'coauthors_max' => (int) ($data['coauthors_max'] ?? 8),
                'gen_participation' => $request->boolean('gen_participation'),
                'gen_communication' => $request->boolean('gen_communication'),
                'partners' => $partners,
                'faq' => $faq,
                'hero_image' => $heroImage,
                'hero_video' => $heroVideo,
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

        return DB::transaction(function () use ($payload, $event, $committees): Event {
            $model = $event ? tap($event)->update($payload) : Event::create($payload);
            $this->syncCommittees($model, $committees);

            return $model;
        });
    }

    /** Remplace les membres de comité de l'événement par ceux du formulaire. */
    private function syncCommittees(Event $event, array $committees): void
    {
        $event->committeeMembers()->delete();

        $rows = collect($committees)->filter(fn ($c) => trim((string) ($c['person_name'] ?? '')) !== '')->values();

        foreach ($rows as $i => $c) {
            $event->committeeMembers()->create([
                'role' => $c['role'] ?? 'organisation',
                'person_name' => $c['person_name'],
                'person_title' => $c['person_title'] ?? null,
                'institution' => $c['institution'] ?? null,
                // Contacts + qualité : alimentent le tableau du comité d'organisation
                // du livret « Document général du colloque ».
                'email' => ($c['email'] ?? null) ?: null,
                'phone' => ($c['phone'] ?? null) ?: null,
                'qualite' => ($c['qualite'] ?? null) ?: null,
                'photo_url' => $c['photo_url'] ?? null,
                'ordre' => $i,
            ]);
        }
    }

    private function resolveSlug(string $slug, string $title, ?Event $event): string
    {
        $base = Str::slug($slug ?: $title) ?: 'event';
        $result = $base;
        $i = 2;
        while (Event::where('slug', $result)->when($event, fn ($q) => $q->whereKeyNot($event->id))->exists()) {
            $result = $base.'-'.$i++;
        }

        return $result;
    }

    private function uniqueColloqueSlug(string $base): string
    {
        $base = $base ?: 'colloque';
        $slug = $base;
        $i = 2;
        while (EventSeries::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
