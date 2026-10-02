<?php

use App\Actions\GenerateReport;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\AI\Exceptions\AiUnavailableException;
use App\Support\Settings;

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

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            return $this->result;
        }
    };
}

function bindFakeAi(?AiProvider $provider): void
{
    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn($provider);

    // La bascule de fournisseurs passe par extract() : on la relaie au fournisseur factice.
    if ($provider === null) {
        $manager->shouldReceive('extract')->andThrow(new AiUnavailableException('Aucun assistant IA n’est configuré (voir Réglages).'));
    } else {
        $manager->shouldReceive('extract')->andReturnUsing(
            fn (string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null) => $provider->extract($instruction, $files, $tier)
        );
    }

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

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.reports.generate'), [
            'edition_id' => (string) $edition->id,
            'aiBrief' => 'Rapport pour le conseil scientifique.',
            'format' => 'pdf', // document rédigé : des sections suffisent
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('ai_report', fn ($r) => $r['title'] === 'Rapport de test' && $r['sections'][0]['heading'] === 'Contexte');
});

it('produit un VRAI tableau quand le format demandé est un tableur', function () {
    bindFakeAi(fakeAiProvider([
        'title' => 'Communications acceptées',
        'table' => [
            'columns' => ['Réf.', 'Titre', 'Axe'],
            'rows' => [['AVD-001', 'Agroécologie', 'Atelier 1'], ['AVD-002', 'Eau', 'Atelier 2']],
        ],
        'sections' => [],
    ]));
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.reports.generate'), [
            'edition_id' => (string) $edition->id,
            'aiBrief' => 'Liste des communications acceptées, colonnes Réf., Titre, Axe.',
            'format' => 'xlsx',
            'scope' => 'subs',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('ai_report', fn ($r) => $r['table']['columns'] === ['Réf.', 'Titre', 'Axe']
            && count($r['table']['rows']) === 2);

    // Le fichier produit contient ce tableau, et non un déversement de la base.
    $entry = collect((array) Settings::get('report_ai_history', []))->first();
    $xlsx = $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.reports.ai-download', [$edition, $entry['id']]));

    $xlsx->assertOk();
    expect($xlsx->headers->get('content-type'))->toContain('spreadsheetml');
});

it('refuse de livrer un tableur rempli de paragraphes', function () {
    // ⚠️ Le défaut d'origine : une demande d'export Excel renvoyait de la prose,
    // et le fichier était complété par un déversement générique de la base.
    bindFakeAi(fakeAiProvider(['title' => 'Bilan', 'sections' => [['heading' => 'Analyse', 'body' => 'Texte.']]]));
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.reports.generate'), [
            'edition_id' => (string) $edition->id,
            'aiBrief' => 'Liste des participants payés.',
            'format' => 'xlsx',
        ])
        ->assertSessionHas('ai_error', fn ($m) => str_contains($m, 'tableau'));
});

it('aligne les lignes du tableau sur ses colonnes', function () {
    // Une ligne trop courte ou trop longue décale tout le tableau dans Excel.
    bindFakeAi(fakeAiProvider([
        'title' => 'Liste',
        'table' => [
            'columns' => ['A', 'B', 'C'],
            'rows' => [['1'], ['1', '2', '3', '4'], ['', '', '']],
        ],
    ]));
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.reports.generate'), [
            'edition_id' => (string) $edition->id,
            'aiBrief' => 'Peu importe.',
            'format' => 'csv',
        ])
        ->assertSessionHas('ai_report', function ($r) {
            // La ligne entièrement vide est écartée, les deux autres sont cadrées.
            return count($r['table']['rows']) === 2
                && $r['table']['rows'][0] === ['1', '', '']
                && $r['table']['rows'][1] === ['1', '2', '3'];
        });
});

it('exige un brief pour générer', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.reports.generate'), [
            'edition_id' => (string) $edition->id,
            'aiBrief' => '',
        ])
        ->assertSessionHasErrors('aiBrief');
});

it('signale l’absence de fournisseur IA', function () {
    bindFakeAi(null);
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.reports.generate'), [
            'edition_id' => (string) $edition->id,
            'aiBrief' => 'Un rapport de synthèse.',
        ])
        ->assertSessionHas('ai_error');
});
