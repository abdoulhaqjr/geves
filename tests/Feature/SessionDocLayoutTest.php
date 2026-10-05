<?php

use App\Support\EventSettings;
use App\Support\SessionDocLayout;

// adminStaff() -> helpers ; openEdition() -> tests/Pest.php

it('configure les colonnes et entêtes des fiches et du PV (par événement)', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.documents.session-layout.save', $event), [
            'sheet_columns' => ['horaire', 'titre'],
            'sheet_columns_label' => ['titre' => 'Intitulé'],
            'sheet_header' => ['salle', 'moderator'],
            'pv_columns' => ['titre', 'auteurs'],
            'pv_header' => ['date'],
            'pv_sections' => ['synthese'],
        ])
        ->assertRedirect();

    $event->refresh();
    expect(EventSettings::get($event, 'session_sheets.columns'))->toBe(['horaire', 'titre']);
    $cols = SessionDocLayout::sheetColumns($event);
    expect(collect($cols)->firstWhere('key', 'titre')['label'])->toBe('Intitulé');
    expect(collect($cols)->pluck('key')->all())->toBe(['horaire', 'titre']);
});

it('rend les fiches de session avec le libellé de colonne personnalisé', function () {
    $event = openEdition();
    $event->sessions()->create(['label' => 'Séance A', 'type' => 'plenary']);
    EventSettings::setMany($event, [
        'session_sheets.columns' => ['horaire', 'titre'],
        'session_sheets.columns_labels' => ['titre' => 'Intitulé'],
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.sessions', $event))
        ->assertOk()
        ->assertSee('Intitulé')
        ->assertSee('Séance A');
});

it('rend le PV imprimable et l’éditeur de mise en page', function () {
    $event = openEdition();
    $event->sessions()->create(['label' => 'Séance B', 'type' => 'plenary']);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.session-pv', $event))
        ->assertOk()
        ->assertSee('Procès-verbal', false)
        ->assertSee('Séance B');

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.session-layout', $event))
        ->assertOk()
        ->assertSee('Mise en page des documents de séance');
});
