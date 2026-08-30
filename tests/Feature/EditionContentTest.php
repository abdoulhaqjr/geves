<?php

use App\Models\Colloque;

// adminStaff() -> AdminSubmissionWorkflowTest

it('enregistre comités, partenaires et FAQ depuis le formulaire d’édition', function () {
    $staff = adminStaff();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), [
        'title' => $edition->title, 'status' => 'open', 'colloque_id' => $edition->colloque_id, 'max_communications' => 3,
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

    $edition->refresh();
    expect($edition->committeeMembers()->count())->toBe(1);
    expect($edition->committeeMembers()->first()->person_name)->toBe('Pr Ama Doe');
    expect($edition->settings['partners'][0]['name'])->toBe('UNESCO');
    expect($edition->settings['faq'][0]['question'])->toBe('Comment soumettre ?');
});

it('enregistre les comités par groupe (organisation / scientifique / supervision)', function () {
    $staff = adminStaff();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), [
        'title' => $edition->title, 'status' => 'open', 'colloque_id' => $edition->colloque_id, 'max_communications' => 3,
        'committees_org' => [['person_name' => 'Pr Kadouza', 'person_title' => 'Président', 'institution' => 'UK']],
        'committees_sci' => [
            ['person_name' => 'Pr Lawson', 'person_title' => 'Président', 'institution' => 'UP'],
            ['person_name' => 'Pr Tchalla', 'person_title' => 'Membre', 'institution' => 'UK'],
        ],
        'committees_sup' => [['person_name' => '', 'person_title' => '', 'institution' => '']], // vide → ignoré
    ])->assertSessionHasNoErrors();

    $edition->refresh();
    expect($edition->committeeMembers()->count())->toBe(3);
    expect($edition->committeeMembers()->where('role', 'scientifique')->count())->toBe(2);
    expect($edition->committeeMembers()->where('role', 'organisation')->first()->person_name)->toBe('Pr Kadouza');
    expect($edition->committeeMembers()->where('role', 'supervision')->count())->toBe(0);
});

it('remplace les comités existants à l’enregistrement (sync)', function () {
    $staff = adminStaff();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
    $edition->committeeMembers()->create(['person_name' => 'Ancien membre', 'role' => 'organisation']);

    $this->actingAs($staff, 'web')->put(route('admin.editions.update', $edition), [
        'title' => $edition->title, 'status' => 'open', 'colloque_id' => $edition->colloque_id, 'max_communications' => 3,
        'committees' => [['role' => 'organisation', 'person_name' => 'Nouveau membre', 'person_title' => '', 'institution' => '', 'photo_url' => '']],
    ])->assertSessionHasNoErrors();

    expect($edition->committeeMembers()->count())->toBe(1);
    expect($edition->committeeMembers()->first()->person_name)->toBe('Nouveau membre');
});

it('affiche comités, partenaires, FAQ et le compte à rebours sur la page publique', function () {
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create([
        'title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026',
        'date_start' => now()->addMonths(3),
        'settings' => [
            'partners' => [['name' => 'UNESCO', 'url' => 'https://unesco.org', 'level' => 'platine']],
            'faq' => [['question' => 'Où loger ?', 'answer' => 'À proximité du campus.']],
        ],
    ]);
    $edition->committeeMembers()->create(['person_name' => 'Pr Ama Doe', 'role' => 'scientifique', 'person_title' => 'Professeure']);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Comités')
        ->assertSee('Pr Ama Doe')
        ->assertSee('UNESCO')
        ->assertSee('Où loger ?')
        ->assertSee('ukcCountdown', false); // compte à rebours (date future)
});

it('n’affiche pas le compte à rebours pour une édition passée', function () {
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create([
        'title' => 'UK 2020', 'status' => 'archived', 'slug' => 'uk-2020',
        'date_start' => now()->subYears(2),
    ]);

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertDontSee('ukcCountdown', false);
});
