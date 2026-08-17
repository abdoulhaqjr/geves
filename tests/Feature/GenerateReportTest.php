<?php

use App\Actions\GenerateReport;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Livewire\Admin\Reports\Index;
use Livewire\Livewire;

// openEdition() -> AccountSubmissionTest ; adminStaff() -> helpers partagés

/** Fournisseur IA factice renvoyant une réponse figée (aucun appel réseau). */
function fakeAiProvider(array $result): AiProvider
{
    return new class($result) implements AiProvider
    {
        public function __construct(private array $result) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = []): array
        {
            return $this->result;
        }
    };
}

function bindFakeAi(?AiProvider $provider): void
{
    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn($provider);
    app()->instance(AiManager::class, $manager);
}

it('génère et assainit un rapport (section vide filtrée)', function () {
    bindFakeAi(fakeAiProvider([
        'title' => 'Rapport DRI',
        'sections' => [
            ['heading' => 'Chiffres clés', 'body' => 'Analyse des inscriptions.'],
            ['heading' => '', 'body' => ''],
        ],
    ]));

    $report = app(GenerateReport::class)->handle(openEdition(), 'Synthèse pour le conseil.');

    expect($report['title'])->toBe('Rapport DRI');
    expect($report['sections'])->toHaveCount(1);
    expect($report['sections'][0]['heading'])->toBe('Chiffres clés');
});

it('affiche le brouillon IA dans les rapports', function () {
    bindFakeAi(fakeAiProvider(['title' => 'Rapport de test', 'sections' => [['heading' => 'Contexte', 'body' => 'Texte.']]]));
    $edition = openEdition();

    Livewire::actingAs(adminStaff(), 'web')
        ->test(Index::class)
        ->set('edition_id', (string) $edition->id)
        ->set('aiBrief', 'Rapport pour le conseil scientifique.')
        ->call('generateReport')
        ->assertHasNoErrors()
        ->assertSet('aiTitle', 'Rapport de test')
        ->assertSee('Contexte');
});

it('exige un brief pour générer', function () {
    Livewire::actingAs(adminStaff(), 'web')
        ->test(Index::class)
        ->set('aiBrief', '')
        ->call('generateReport')
        ->assertHasErrors('aiBrief');
});

it('signale l’absence de fournisseur IA', function () {
    bindFakeAi(null);
    $edition = openEdition();

    Livewire::actingAs(adminStaff(), 'web')
        ->test(Index::class)
        ->set('edition_id', (string) $edition->id)
        ->set('aiBrief', 'Un rapport de synthèse.')
        ->call('generateReport')
        ->assertSet('aiError', fn ($e) => filled($e));
});
