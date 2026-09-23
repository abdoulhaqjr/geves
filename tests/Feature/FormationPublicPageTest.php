<?php

use App\Models\Formation;
use App\Support\EditionSettings;

// openEdition() -> tests/Pest.php

it('affiche la section Formations quand le module est activé et une formation est publique', function () {
    $edition = openEdition();
    EditionSettings::setMany($edition, ['formation.enabled' => true]);
    Formation::create([
        'edition_id' => $edition->id, 'title' => 'Rédaction scientifique',
        'published' => true, 'public' => true, 'fee' => 0,
    ]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Rédaction scientifique')
        ->assertSee('#formations', false); // ancre de la nav scrollspy
});

it('ne montre pas les formations si le module est désactivé', function () {
    $edition = openEdition();
    Formation::create([
        'edition_id' => $edition->id, 'title' => 'Formation cachée module',
        'published' => true, 'public' => true, 'fee' => 0,
    ]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertDontSee('Formation cachée module');
});

it('ne montre pas une formation non publique même module activé', function () {
    $edition = openEdition();
    EditionSettings::setMany($edition, ['formation.enabled' => true]);
    Formation::create([
        'edition_id' => $edition->id, 'title' => 'Interne uniquement',
        'published' => true, 'public' => false, 'fee' => 0,
    ]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertDontSee('Interne uniquement');
});
