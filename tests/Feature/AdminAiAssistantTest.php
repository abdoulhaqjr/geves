<?php

use App\Livewire\Admin\AiAssistant;
use Livewire\Livewire;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest ; adminStaff() -> helpers

it('le copilote admin répond au staff', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Voici une synthèse de l’édition active.']));

    Livewire::actingAs(adminStaff(), 'web')
        ->test(AiAssistant::class)
        ->assertSee('copilote', false)
        ->set('input', 'Résume l’édition active.')
        ->call('send')
        ->assertSee('synthèse')
        ->assertSet('input', '');
});

it('exige une saisie', function () {
    Livewire::actingAs(adminStaff(), 'web')
        ->test(AiAssistant::class)
        ->set('input', '')
        ->call('send')
        ->assertHasErrors('input');
});
