<?php

use App\AI\AiManager;
use App\AI\AiRouter;
use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiUnavailableException;
use App\AI\Providers\GeminiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/** Fournisseur factice : clé, capacité PDF et comportement d'extraction configurables. */
function aiProv(string $key, bool $pdf, Closure $onExtract): AiProvider
{
    return new class($key, $pdf, $onExtract) implements AiProvider
    {
        public function __construct(private string $k, private bool $pdf, private Closure $fn) {}

        public function key(): string
        {
            return $this->k;
        }

        public function label(): string
        {
            return $this->k;
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return $this->pdf;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            return ($this->fn)($instruction, $files, $tier);
        }
    };
}

/** Gestionnaire dont la liste de fournisseurs disponibles est imposée. */
function aiManagerWith(array $providers): AiManager
{
    return new class($providers) extends AiManager
    {
        /** @param array<int, AiProvider> $p */
        public function __construct(private array $p) {}

        public function available(): array
        {
            return $this->p;
        }
    };
}

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

it('ne répète pas une requête avec une clé Gemini refusée', function () {
    config([
        'services.gemini.key' => 'invalid',
        'services.gemini.model' => 'gemini-default',
        'services.gemini.model_deep' => 'gemini-deep',
    ]);

    Http::fake(['*generativelanguage.googleapis.com*' => Http::response('invalid key', 401)]);

    expect(fn () => (new GeminiProvider)->extract('Analyse ce document', [], 'deep'))
        ->toThrow(RequestException::class);
    Http::assertSentCount(1);
});

it('indique quel fournisseur refuse sa clé quand les replis échouent', function () {
    config(['services.ai.default' => 'gemini', 'services.gemini.key' => 'invalid']);
    Http::fake(['*generativelanguage.googleapis.com*' => Http::response('invalid key', 401)]);

    $manager = aiManagerWith([
        new GeminiProvider,
        aiProv('claude', true, fn () => throw new AiUnavailableException('indisponible')),
    ]);

    expect(fn () => $manager->extract('Analyse ce document', [['mime' => 'application/pdf', 'data' => 'x']]))
        ->toThrow(AiUnavailableException::class, 'Gemini');
});

it('passe au fournisseur suivant sans retenter une connexion expirée', function () {
    config(['services.gemini.key' => 'k']);
    $calls = 0;
    Http::fake(function () use (&$calls) {
        $calls++;
        throw new ConnectionException('Connection timed out');
    });

    expect(fn () => (new GeminiProvider)->extract('Analyse ce document'))
        ->toThrow(AiUnavailableException::class, 'ne répond pas');
    expect($calls)->toBe(1);
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

/* --------------------------- Bascule entre fournisseurs ------------------- */

it('bascule sur un autre fournisseur quand le premier échoue', function () {
    config(['services.ai.default' => 'gemini']);
    $calls = [];

    $first = aiProv('gemini', true, function () use (&$calls) {
        $calls[] = 'gemini';
        throw new AiUnavailableException('surcharge');
    });
    $second = aiProv('claude', true, function () use (&$calls) {
        $calls[] = 'claude';

        return ['answer' => 'via claude'];
    });

    $result = aiManagerWith([$first, $second])->extract('question');

    expect($result['answer'])->toBe('via claude');
    expect($calls)->toBe(['gemini', 'claude']); // le défaut d'abord, puis bascule
});

it('ignore les fournisseurs incapables de lire les PDF', function () {
    config(['services.ai.default' => 'openai']);

    $noPdf = aiProv('openai', false, fn () => throw new RuntimeException('ne doit pas être appelé'));
    $pdf = aiProv('gemini', true, fn () => ['answer' => 'PDF lu']);

    $result = aiManagerWith([$noPdf, $pdf])->extract('q', [['mime' => 'application/pdf', 'data' => 'x']]);

    expect($result['answer'])->toBe('PDF lu');
});

it('signale l’absence de fournisseur capable de lire les PDF', function () {
    config(['services.ai.default' => 'openai']);

    $manager = aiManagerWith([aiProv('openai', false, fn () => [])]);

    expect(fn () => $manager->extract('q', [['mime' => 'application/pdf', 'data' => 'x']]))
        ->toThrow(AiUnavailableException::class, 'PDF');
});

it('signale l’indisponibilité quand tous les fournisseurs échouent', function () {
    config(['services.ai.default' => 'gemini']);

    $manager = aiManagerWith([
        aiProv('gemini', true, fn () => throw new AiUnavailableException('x')),
        aiProv('claude', true, fn () => throw new AiUnavailableException('y')),
    ]);

    expect(fn () => $manager->extract('q'))->toThrow(AiUnavailableException::class);
});
