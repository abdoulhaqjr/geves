<?php

use App\Models\EventSeries;

// adminStaff() -> AdminSubmissionWorkflowTest

it('enregistre comités, partenaires et FAQ depuis le formulaire d’événement', function () {
    $staff = adminStaff();
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), [
        'title' => $event->title, 'status' => 'open', 'event_series_id' => $event->event_series_id, 'max_communications' => 3,
        'committees' => [
            ['role' => 'scientifique', 'person_name' => 'Pr Ama Doe', 'person_title' => 'Professeure', 'institution' => 'UK', 'photo_url' => ''],
        ],
        'partners' => [
            ['name' => 'UNESCO', 'url' => 'https://unesco.org', 'logo_url' => '', 'level' => 'platine'],
        ],
        'faq' => [
            ['question' => 'Comment soumettre ?', 'answer' => 'Depuis votre espace participant.'],
        ],
    ])->assertSessionHasNoErrors();

    $event->refresh();
    expect($event->committeeMembers()->count())->toBe(1);
    expect($event->committeeMembers()->first()->person_name)->toBe('Pr Ama Doe');
    expect($event->settings['partners'][0]['name'])->toBe('UNESCO');
    expect($event->settings['faq'][0]['question'])->toBe('Comment soumettre ?');
});

it('enregistre les comités par groupe (organisation / scientifique / supervision)', function () {
    $staff = adminStaff();
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), [
        'title' => $event->title, 'status' => 'open', 'event_series_id' => $event->event_series_id, 'max_communications' => 3,
        'committees_org' => [[
            'person_name' => 'Pr Kadouza', 'person_title' => 'Professeur', 'institution' => 'UK',
            'email' => 'kadouza@example.tg', 'phone' => '+22890000001', 'qualite' => 'Président',
        ]],
        'committees_sci' => [
            ['person_name' => 'Pr Lawson', 'person_title' => 'Président', 'institution' => 'UP'],
            ['person_name' => 'Pr Tchalla', 'person_title' => 'Membre', 'institution' => 'UK'],
        ],
        'committees_sup' => [['person_name' => '', 'person_title' => '', 'institution' => '']], // vide → ignoré
    ])->assertSessionHasNoErrors();

    $event->refresh();
    expect($event->committeeMembers()->count())->toBe(3);
    expect($event->committeeMembers()->where('role', 'scientifique')->count())->toBe(2);
    $organisation = $event->committeeMembers()->where('role', 'organisation')->first();
    expect($organisation->person_name)->toBe('Pr Kadouza')
        ->and($organisation->email)->toBe('kadouza@example.tg')
        ->and($organisation->phone)->toBe('+22890000001')
        ->and($organisation->qualite)->toBe('Président');
    expect($event->committeeMembers()->where('role', 'supervision')->count())->toBe(0);

    // Les valeurs sauvegardées sont réinjectées dans les tableaux Alpine du
    // formulaire : le champ ne revient donc plus vide après Enregistrer.
    $this->actingAs($staff, 'web')
        ->get(route('admin.evenements.edit', ['event' => $event, 'tab' => 'committees']))
        ->assertOk()
        ->assertSee('kadouza@example.tg')
        ->assertSee('+22890000001')
        ->assertSee('Président');
});

it('remplace les comités existants à l’enregistrement (sync)', function () {
    $staff = adminStaff();
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
    $event->committeeMembers()->create(['person_name' => 'Ancien membre', 'role' => 'organisation']);

    $this->actingAs($staff, 'web')->put(route('admin.evenements.update', $event), [
        'title' => $event->title, 'status' => 'open', 'event_series_id' => $event->event_series_id, 'max_communications' => 3,
        'committees' => [['role' => 'organisation', 'person_name' => 'Nouveau membre', 'person_title' => '', 'institution' => '', 'photo_url' => '']],
    ])->assertSessionHasNoErrors();

    expect($event->committeeMembers()->count())->toBe(1);
    expect($event->committeeMembers()->first()->person_name)->toBe('Nouveau membre');
});

it('affiche comités, partenaires, FAQ et le compte à rebours sur la page publique', function () {
    $series = EventSeries::create(['name' => 'EventSeries UK', 'slug' => 'uk']);
    $event = $series->events()->create([
        'title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026',
        'date_start' => now()->addMonths(3),
        'settings' => [
            'partners' => [['name' => 'UNESCO', 'url' => 'https://unesco.org', 'level' => 'platine']],
            'faq' => [['question' => 'Où loger ?', 'answer' => 'À proximité du campus.']],
        ],
    ]);
    $event->committeeMembers()->create(['person_name' => 'Pr Ama Doe', 'role' => 'scientifique', 'person_title' => 'Professeure']);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertSee('Comités')
        ->assertSee('Pr Ama Doe')
        ->assertSee('UNESCO')
        ->assertSee('Où loger ?')
        ->assertSee('ukcCountdown', false); // compte à rebours (date future)
});

it('n’affiche pas le compte à rebours pour un événement passé', function () {
    $series = EventSeries::create(['name' => 'EventSeries UK', 'slug' => 'uk']);
    $event = $series->events()->create([
        'title' => 'UK 2020', 'status' => 'archived', 'slug' => 'uk-2020',
        'date_start' => now()->subYears(2),
    ]);

    $this->get(route('public.colloque', $event->slug))
        ->assertOk()
        ->assertDontSee('ukcCountdown', false);
});
