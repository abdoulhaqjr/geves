<?php

use App\Actions\ExtractEventFromDocuments;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\AI\Providers\AnthropicProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

// adminStaff() -> AdminSubmissionWorkflowTest

function fakeAiManager(): AiManager
{
    $provider = new class implements AiProvider
    {
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
            return [
                'title' => 'EventSeries IA 2027',
                'year' => 2027,
                'date_start' => '2027-11-10',
                'date_end' => '2027-11-12',
                'submission_deadline' => '2027-09-30',
                'location' => 'Kara, Togo',
                'tracks' => ['Axe 1', 'Axe 2'],
                'fees' => [['label' => 'Doctorant', 'amount' => 10000]],
                'committees' => [['role' => 'scientifique', 'person_name' => 'Pr X', 'person_title' => 'Professeur', 'institution' => 'UK']],
                'partners' => [['name' => 'UNESCO', 'url' => 'https://unesco.org', 'level' => 'or']],
                'faq' => [['question' => 'Q ?', 'answer' => 'R.']],
            ];
        }
    };

    return new class($provider) extends AiManager
    {
        public function __construct(private AiProvider $p) {}

        public function available(): array
        {
            return [$this->p];
        }

        public function resolve(bool $needsPdf = false): ?AiProvider
        {
            return $this->p;
        }
    };
}

/* --------------------------- AiManager (résolution) ----------------------- */

it('résout le fournisseur d’IA par défaut et privilégie un lecteur PDF', function () {
    config([
        'services.openai.key' => 'sk-openai',
        'services.anthropic.key' => 'sk-ant',
        'services.ai.default' => 'openai',
    ]);

    $manager = new AiManager;

    expect(collect($manager->available())->map->key()->all())->toContain('openai', 'claude');
    expect($manager->resolve(false)->key())->toBe('openai');           // défaut
    expect($manager->resolve(true)->key())->toBe('claude');            // PDF → Claude (OpenAI ne lit pas les PDF)
});

/* --------------------------- Provider (shape HTTP) ------------------------ */

it('appelle l’API Anthropic et décode le JSON', function () {
    config(['services.anthropic.key' => 'sk-ant-key', 'services.anthropic.model' => 'claude-test']);

    Http::fake([
        '*/v1/messages' => Http::response(['content' => [['type' => 'text', 'text' => '{"title":"EventSeries 2027","year":2027}']]]),
    ]);

    $result = (new AnthropicProvider)->extract('Extrais…', [['mime' => 'application/pdf', 'data' => base64_encode('pdf')]]);

    expect($result['title'])->toBe('EventSeries 2027');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/v1/messages')
        && $req['model'] === 'claude-test'
        && $req->hasHeader('x-api-key', 'sk-ant-key'));
});

/* --------------------------- Action (normalisation) ----------------------- */

it('normalise les métadonnées extraites', function () {
    $this->app->instance(AiManager::class, fakeAiManager());

    $out = app(ExtractEventFromDocuments::class)->handle([['mime' => 'application/pdf', 'data' => 'x']]);

    expect($out['title'])->toBe('EventSeries IA 2027');
    expect($out['year'])->toBe(2027);
    expect($out['date_start'])->toBe('2027-11-10');
    expect($out['tracks'])->toBe(['Axe 1', 'Axe 2']);
    expect($out['fees'][0]['amount'])->toBe(10000.0);
    expect($out['committees'][0]['role'])->toBe('scientifique');
});

/* --------------------------- Form (pré-remplissage) ----------------------- */

it('pré-remplit le formulaire d’événement depuis l’assistant IA', function () {
    $this->app->instance(AiManager::class, fakeAiManager());
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.evenements.ai-assist'), [
        'aiFiles' => [UploadedFile::fake()->create('appel.pdf', 50, 'application/pdf')],
    ])->assertSessionHasNoErrors()->assertSessionHas('ai_extracted');

    // Les valeurs extraites sont flashées puis pré-remplies au rechargement du formulaire.
    $d = session('ai_extracted');
    expect($d['title'])->toBe('EventSeries IA 2027');
    expect($d['year'])->toBe(2027);
    expect($d['location'])->toBe('Kara, Togo');
    expect($d['tracks'])->toBe("Axe 1\nAxe 2");
});
