<?php

use App\Models\Formation;

// openEdition() -> tests/Pest.php

it('affiche la section Formations quand le module est activé et une formation est publique', function () {
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], ['formation_enabled' => true])]);
    Formation::create([
        'event_id' => $event->id, 'title' => 'Rédaction scientifique',
        'published' => true, 'public' => true, 'fee' => 0,
    ]);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('Rédaction scientifique')
        ->assertSee('#formations', false); // ancre de la nav scrollspy
});

it('ne montre pas les formations si le module est désactivé', function () {
    $event = openEdition();
    Formation::create([
        'event_id' => $event->id, 'title' => 'Formation cachée module',
        'published' => true, 'public' => true, 'fee' => 0,
    ]);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertDontSee('Formation cachée module');
});

it('ne montre pas une formation non publique même module activé', function () {
    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], ['formation_enabled' => true])]);
    Formation::create([
        'event_id' => $event->id, 'title' => 'Interne uniquement',
        'published' => true, 'public' => false, 'fee' => 0,
    ]);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertDontSee('Interne uniquement');
});
