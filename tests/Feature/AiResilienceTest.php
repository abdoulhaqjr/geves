<?php

use App\AI\AiRouter;
use App\AI\Exceptions\AiUnavailableException;
use App\AI\Providers\GeminiProvider;
use Illuminate\Support\Facades\Http;

/* --------------------------- Relance transparente (503) ------------------- */

it('rejoue une requête IA en 503 puis réussit, de façon transparente', function () {
    config(['services.gemini.key' => 'k', 'services.gemini.model' => 'gemini-test']);

    // 1re tentative : 503 (surcharge) ; 2e : succès.
    Http::fakeSequence('*generativelanguage.googleapis.com*')
        ->push('overloaded', 503)
        ->push(['candidates' => [['content' => ['parts' => [['text' => '{"answer":"ok"}']]]]]], 200);

    $result = (new GeminiProvider)->extract('Bonjour ?');

    expect($result['answer'])->toBe('ok');
    Http::assertSentCount(2); // une relance a bien eu lieu
});

it('lève un message clair après trois échecs 503', function () {
    config(['services.gemini.key' => 'k', 'services.gemini.model' => 'gemini-test']);

    Http::fake(['*generativelanguage.googleapis.com*' => Http::response('overloaded', 503)]);

    expect(fn () => (new GeminiProvider)->extract('Bonjour ?'))
        ->toThrow(AiUnavailableException::class, 'sollicité');

    Http::assertSentCount(3); // trois tentatives
});

it('se replie sur le modèle par défaut si le palier est refusé (4xx)', function () {
    config([
        'services.gemini.key' => 'k',
        'services.gemini.model' => 'gemini-defaut',
        'services.gemini.model_fast' => 'gemini-palier-absent',
    ]);

    // Le modèle de palier renvoie 404 (indisponible) ; le modèle par défaut réussit.
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'gemini-palier-absent')) {
            return Http::response('model not found', 404);
        }

        return Http::response(['candidates' => [['content' => ['parts' => [['text' => '{"answer":"repli"}']]]]]], 200);
    });

    $result = (new GeminiProvider)->extract('Bonjour ?', [], 'fast');

    expect($result['answer'])->toBe('repli');
});

/* --------------------------- Routeur de modèle ---------------------------- */

it('choisit le palier économie pour une question courte et simple', function () {
    expect((new AiRouter)->tier('chat_public', 'Quels sont les tarifs ?'))->toBe('fast');
});

it('choisit le palier qualité pour une demande analytique', function () {
    expect((new AiRouter)->tier('chat_admin', 'Analyse et compare les axes puis recommande une stratégie.'))->toBe('deep');
});

it('force le palier qualité pour les tâches lourdes', function () {
    expect((new AiRouter)->tier('report'))->toBe('deep');
    expect((new AiRouter)->tier('claim_analysis'))->toBe('deep');
    expect((new AiRouter)->tier('chat_public', 'question', hasFiles: true))->toBe('deep');
});
