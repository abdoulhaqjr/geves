<?php

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest ; adminStaff() -> helpers

it('le copilote admin répond au staff', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Voici une synthèse de l’édition active.']));

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.ai'))
        ->assertOk()
        ->assertSee('copilote', false);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Résume l’édition active.'])
        ->assertOk()
        ->assertJsonPath('answer', 'Voici une synthèse de l’édition active.');
});

it('exige une saisie', function () {
    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => ''])
        ->assertStatus(422);
});
