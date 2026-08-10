<?php

use App\Livewire\Admin\Editions\Form;
use App\Models\Colloque;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest

it('enregistre comités, partenaires et FAQ depuis le formulaire d’édition', function () {
    $staff = adminStaff();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);

    Livewire::actingAs($staff, 'web')
        ->test(Form::class, ['edition' => $edition])
        ->set('committees', [
            ['role' => 'scientifique', 'person_name' => 'Pr Ama Doe', 'person_title' => 'Professeure', 'institution' => 'UK', 'photo_url' => ''],
        ])
        ->set('partners', [
            ['name' => 'UNESCO', 'url' => 'https://unesco.org', 'logo_url' => '', 'level' => 'platine'],
        ])
        ->set('faq', [
            ['question' => 'Comment soumettre ?', 'answer' => 'Depuis votre espace participant.'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $edition->refresh();
    expect($edition->committeeMembers()->count())->toBe(1);
    expect($edition->committeeMembers()->first()->person_name)->toBe('Pr Ama Doe');
    expect($edition->settings['partners'][0]['name'])->toBe('UNESCO');
    expect($edition->settings['faq'][0]['question'])->toBe('Comment soumettre ?');
});

it('remplace les comités existants à l’enregistrement (sync)', function () {
    $staff = adminStaff();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
    $edition->committeeMembers()->create(['person_name' => 'Ancien membre', 'role' => 'organisation']);

    Livewire::actingAs($staff, 'web')
        ->test(Form::class, ['edition' => $edition])
        ->set('committees', [['role' => 'organisation', 'person_name' => 'Nouveau membre', 'person_title' => '', 'institution' => '', 'photo_url' => '']])
        ->call('save')
        ->assertHasNoErrors();

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
