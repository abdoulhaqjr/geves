<?php

// openEdition() -> AccountSubmissionTest

it('affiche la page d’accueil publique', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('UK Colloques')
        ->assertSee('séminaires, colloques et journées scientifiques', false);
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

it('renvoie 404 pour une édition inconnue', function () {
    $this->get(route('public.archive', 'edition-inexistante'))->assertNotFound();
});

it('n’expose pas une édition en brouillon', function () {
    $edition = openEdition();
    $edition->update(['status' => 'draft']);

    $this->get(route('public.archive', $edition->slug))->assertNotFound();
});
