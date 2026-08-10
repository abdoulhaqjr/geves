<?php

use App\Actions\IssueAttestation;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Livewire\Admin\Documents\TemplateDesigner;
use App\Models\Colloque;
use App\Models\DocumentTemplate;
use App\Models\User;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

function fakeLayoutManager(string $text): AiManager
{
    $provider = new class($text) implements AiProvider
    {
        public function __construct(public string $t) {}

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
            return ['width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#009933',
                'elements' => [['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 300, 'h' => 30, 'text' => $this->t, 'size' => 18, 'weight' => 700, 'color' => '#111111', 'align' => 'center']]];
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

/* ------------------------------ Éditeur / IA ------------------------------ */

it('génère une mise en page par IA dans l’éditeur', function () {
    $this->app->instance(AiManager::class, fakeLayoutManager('GEN {name}'));
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::actingAs($staff, 'web')
        ->test(TemplateDesigner::class)
        ->set('edition_id', (string) $edition->id)
        ->set('kind', 'badge')
        ->set('prompt', 'sobre, bleu UK')
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('layoutJson', fn ($v) => str_contains($v, 'GEN'));
});

it('enregistre le modèle', function () {
    $staff = adminStaff();
    $edition = openEdition();

    Livewire::actingAs($staff, 'web')
        ->test(TemplateDesigner::class)
        ->set('edition_id', (string) $edition->id)
        ->set('kind', 'attestation')
        ->set('type', 'communication')
        ->call('save')
        ->assertHasNoErrors();

    expect(DocumentTemplate::where('edition_id', $edition->id)->where('kind', 'attestation')->where('type', 'communication')->exists())->toBeTrue();
});

it('rejette un JSON de mise en page invalide', function () {
    $staff = adminStaff();

    Livewire::actingAs($staff, 'web')
        ->test(TemplateDesigner::class)
        ->set('layoutJson', 'ceci n’est pas du json')
        ->call('applyJson')
        ->assertHasErrors('layoutJson');
});

it('protège l’éditeur de modèles sans permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.templates'))->assertForbidden();
});

/* ------------------------- Branchement du rendu --------------------------- */

it('rend l’attestation via le modèle de l’édition si présent', function () {
    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'participation');

    DocumentTemplate::create([
        'edition_id' => $edition->id, 'kind' => 'attestation', 'type' => 'participation',
        'layout' => ['width' => 900, 'height' => 636, 'bg' => '#fff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'text', 'x' => 50, 'y' => 50, 'w' => 800, 'h' => 40, 'text' => 'CERTIF {name}'],
                ['type' => 'qr', 'x' => 700, 'y' => 500, 'size' => 90, 'value' => '{verify_url}'],
            ]],
    ]);

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('CERTIF Awa Kodjo')
        ->assertSee('<svg', false);
});

it('rend la planche de badges via le modèle si présent', function () {
    $staff = adminStaff();
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
    $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    DocumentTemplate::create([
        'edition_id' => $edition->id, 'kind' => 'badge', 'type' => 'default',
        'layout' => ['width' => 320, 'height' => 440, 'bg' => '#fff', 'accent' => '#223ea4',
            'elements' => [['type' => 'text', 'x' => 10, 'y' => 200, 'w' => 300, 'h' => 30, 'text' => 'CARTE {name}']]],
    ]);

    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $edition))
        ->assertOk()
        ->assertSee('CARTE Awa Kodjo');
});
