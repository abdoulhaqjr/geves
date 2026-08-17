<?php

use App\Actions\AskGesco;
use App\Livewire\Public\GescoChat;
use Livewire\Livewire;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest

it('répond à une question via l’agent Gesco', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Pour soumettre, connectez-vous à votre espace participant.']));

    $answer = app(AskGesco::class)->handle([], 'Comment soumettre une communication ?');

    expect($answer)->toContain('espace participant');
});

it('affiche l’accueil puis répond dans le chat', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Les tarifs dépendent de votre catégorie.']));

    Livewire::test(GescoChat::class)
        ->assertSee('Gesco')
        ->set('input', 'Quels sont les tarifs ?')
        ->call('send')
        ->assertSee('Les tarifs dépendent')
        ->assertSet('input', '');
});

it('refuse un message vide', function () {
    Livewire::test(GescoChat::class)
        ->set('input', '')
        ->call('send')
        ->assertHasErrors('input');
});

it('gère proprement l’absence de fournisseur IA', function () {
    bindFakeAi(null);

    Livewire::test(GescoChat::class)
        ->set('input', 'Bonjour')
        ->call('send')
        ->assertSee('indisponible');
});
