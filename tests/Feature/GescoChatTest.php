<?php

use App\Actions\AskGesco;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest

it('répond à une question via l’agent Gesco', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Pour soumettre, connectez-vous à votre espace participant.']));

    $answer = app(AskGesco::class)->handle([], 'Comment soumettre une communication ?');

    expect($answer)->toContain('espace participant');
});

it('affiche la page dédiée de l’assistante Gesco', function () {
    $this->get(route('public.gesco'))
        ->assertOk()
        ->assertSee('Gesco');
});

it('répond dans le chat via l’endpoint JSON', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Les tarifs dépendent de votre catégorie.']));

    $this->postJson(route('gesco.ask'), ['question' => 'Quels sont les tarifs ?'])
        ->assertOk()
        ->assertJsonPath('answer', 'Les tarifs dépendent de votre catégorie.');
});

it('conserve l’historique de conversation', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Le programme est publié sur la page du colloque.']));

    $this->postJson(route('gesco.ask'), [
        'question' => 'Où est le programme ?',
        'history' => [
            ['role' => 'bot', 'text' => 'Bonjour'],
            ['role' => 'user', 'text' => 'Salut'],
        ],
    ])
        ->assertOk()
        ->assertJsonPath('answer', 'Le programme est publié sur la page du colloque.');
});

it('refuse un message vide', function () {
    $this->postJson(route('gesco.ask'), ['question' => ''])
        ->assertStatus(422);
});

it('gère proprement l’absence de fournisseur IA', function () {
    bindFakeAi(null);

    $this->postJson(route('gesco.ask'), ['question' => 'Bonjour'])
        ->assertOk()
        ->assertJsonFragment(['answer' => 'L’assistance Gesco est momentanément indisponible.']);
});
