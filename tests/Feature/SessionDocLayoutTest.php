<?php

use App\Support\EditionSettings;
use App\Support\SessionDocLayout;

// adminStaff() -> helpers ; openEdition() -> tests/Pest.php

it('configure les colonnes et entêtes des fiches et du PV (par édition)', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.documents.session-layout.save', $edition), [
            'sheet_columns' => ['horaire', 'titre'],
            'sheet_columns_label' => ['titre' => 'Intitulé'],
            'sheet_header' => ['salle', 'moderator'],
            'pv_columns' => ['titre', 'auteurs'],
            'pv_header' => ['date'],
            'pv_sections' => ['synthese'],
        ])
        ->assertRedirect();

    $edition->refresh();
    expect(EditionSettings::get($edition, 'session_sheets.columns'))->toBe(['horaire', 'titre']);
    $cols = SessionDocLayout::sheetColumns($edition);
    expect(collect($cols)->firstWhere('key', 'titre')['label'])->toBe('Intitulé');
    expect(collect($cols)->pluck('key')->all())->toBe(['horaire', 'titre']);
});

it('rend les fiches de session avec le libellé de colonne personnalisé', function () {
    $edition = openEdition();
    $edition->sessions()->create(['label' => 'Séance A', 'type' => 'plenary']);
    EditionSettings::setMany($edition, [
        'session_sheets.columns' => ['horaire', 'titre'],
        'session_sheets.columns_labels' => ['titre' => 'Intitulé'],
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.sessions', $edition))
        ->assertOk()
        ->assertSee('Intitulé')
        ->assertSee('Séance A');
});

it('rend le PV imprimable et l’éditeur de mise en page', function () {
    $edition = openEdition();
    $edition->sessions()->create(['label' => 'Séance B', 'type' => 'plenary']);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.session-pv', $edition))
        ->assertOk()
        ->assertSee('Procès-verbal', false)
        ->assertSee('Séance B');

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.session-layout', $edition))
        ->assertOk()
        ->assertSee('Mise en page des documents de séance');
});
