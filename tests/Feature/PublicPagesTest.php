<?php

// openEdition() -> AccountSubmissionTest

it('affiche la page d’accueil publique', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('UK Colloques')
        ->assertSee('séminaires, colloques et journées scientifiques', false);
});

it('affiche la galerie du dernier colloque sur l’accueil', function () {
    $edition = openEdition();
    $edition->update(['status' => 'archived', 'date_start' => '2024-03-18', 'date_end' => '2024-03-20']);
    $edition->media()->create(['type' => 'photo', 'url' => 'https://example.test/g1.jpg', 'title' => 'Ouverture', 'ordre' => 1]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Galerie — dernier colloque', false)
        ->assertSee('galleryData', false)
        ->assertSee('https://example.test/g1.jpg');
});

it('n’affiche pas la galerie sur l’accueil sans média passé', function () {
    openEdition(); // publiée mais non passée, sans média

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Galerie — dernier colloque', false);
});

it('affiche la liste des archives', function () {
    openEdition(); // édition publiée mais non passée → liste vide, page OK

    $this->get(route('public.archives'))
        ->assertOk()
        ->assertSee('Colloques passés');
});

it('liste une édition passée dans les archives', function () {
    $edition = openEdition();
    $edition->update(['status' => 'archived', 'date_start' => '2024-03-18', 'date_end' => '2024-03-20', 'location' => 'Kara']);

    $this->get(route('public.archives'))
        ->assertOk()
        ->assertSee('UK 2026')
        ->assertSee('Kara');
});

it('affiche le détail d’une édition avec sa galerie', function () {
    $edition = openEdition();
    $edition->media()->create(['type' => 'photo', 'url' => 'https://example.test/p1.jpg', 'title' => 'Plénière', 'ordre' => 1]);

    $this->get(route('public.archive', $edition->slug))
        ->assertOk()
        ->assertSee('UK 2026')
        ->assertSee('Axes thématiques')      // settings.tracks
        ->assertSee('Galerie')
        ->assertSee('galleryData', false);   // JSON du lightbox
});

it('affiche l’état vide du déroulement sans colloque en cours', function () {
    $this->get(route('public.deroulement'))
        ->assertOk()
        ->assertSee('Aucun colloque en cours', false);
});

it('affiche le déroulement d’un colloque en cours', function () {
    $edition = openEdition();
    $edition->update([
        'title' => 'Colloque en direct',
        'date_start' => now()->subDay(),
        'date_end' => now()->addDay(),
        'location' => 'Kara',
    ]);
    $atelier = $edition->ateliers()->create(['title' => 'Atelier 1', 'ordre' => 1]);
    $edition->sessions()->create([
        'atelier_id' => $atelier->id, 'type' => 'atelier', 'label' => 'Session en cours',
        'room' => 'Salle A', 'session_day' => now()->toDateString(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
        'meeting_status' => 'live', 'meeting_provider' => 'jitsi', 'ordre' => 1,
    ]);

    $this->get(route('public.deroulement'))
        ->assertOk()
        ->assertSee('Colloque en direct')
        ->assertSee('En ce moment', false)
        ->assertSee('Session en cours');
});

it('affiche la landing de vérification d’attestation', function () {
    $this->get(route('attestation.verify.landing'))
        ->assertOk()
        ->assertSee('Vérifier une attestation');
});

it('redirige la saisie d’un code vers le résultat', function () {
    $uuid = '11111111-2222-3333-4444-555555555555';

    $this->get(route('attestation.verify.landing', ['code' => $uuid]))
        ->assertRedirect(route('attestation.verify', $uuid));
});

it('extrait l’UUID d’une URL de vérification collée', function () {
    $uuid = '11111111-2222-3333-4444-555555555555';

    $this->get(route('attestation.verify.landing', ['code' => 'https://gesco.test/verifier/'.$uuid]))
        ->assertRedirect(route('attestation.verify', $uuid));
});

it('renvoie 404 pour une édition inconnue', function () {
    $this->get(route('public.archive', 'edition-inexistante'))->assertNotFound();
});

it('n’expose pas une édition en brouillon', function () {
    $edition = openEdition();
    $edition->update(['status' => 'draft']);

    $this->get(route('public.archive', $edition->slug))->assertNotFound();
});
