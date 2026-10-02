<?php

use App\Actions\IssueAttestation;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Documents\LayoutRenderer;
use App\Models\Colloque;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
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

    $resp = $this->actingAs($staff, 'web')->postJson(route('admin.documents.templates.generate'), [
        'edition_id' => (string) $edition->id,
        'kind' => 'badge',
        'type' => 'default',
        'prompt' => 'sobre, bleu UK',
    ])->assertOk();

    expect(json_encode($resp->json('layout')))->toContain('GEN');
});

it('enregistre le modèle', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->postJson(route('admin.documents.templates.save'), [
        'edition_id' => (string) $edition->id,
        'kind' => 'attestation',
        'type' => 'communication',
        'layout' => ['width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4', 'elements' => []],
    ])->assertOk()->assertJsonPath('ok', true);

    expect(DocumentTemplate::where('edition_id', $edition->id)->where('kind', 'attestation')->where('type', 'communication')->exists())->toBeTrue();
});

it('enregistre la mise en page de l’éditeur visuel (sanitisée)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->postJson(route('admin.documents.templates.save'), [
        'edition_id' => (string) $edition->id,
        'kind' => 'badge',
        'layout' => [
            'width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'text', 'x' => 5000, 'y' => 10, 'w' => 300, 'h' => 30, 'text' => 'EDIT {name}', 'size' => 20, 'weight' => 700, 'color' => '#111111', 'align' => 'center'],
                ['type' => 'chaos', 'x' => 0, 'y' => 0], // type non listé → ignoré
            ],
        ],
    ])->assertOk();

    $tpl = DocumentTemplate::where('edition_id', $edition->id)->where('kind', 'badge')->where('type', 'default')->first();

    expect($tpl)->not->toBeNull();
    // Coordonnée bornée par le sanitizer (x ≤ largeur), type inconnu écarté.
    expect($tpl->layout['elements'])->toHaveCount(1);
    expect($tpl->layout['elements'][0]['x'])->toBeLessThanOrEqual(320);
    expect($tpl->layout['elements'][0]['text'])->toBe('EDIT {name}');
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

it('enregistre le nom saisi dans le concepteur', function () {
    // ⚠️ Le nom était affiché et modifiable dans l'en-tête, mais ni transmis ni
    // enregistré : renommer n'avait aucun effet, alors que le message de
    // confirmation citait le nouveau nom.
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.documents.templates.save'), [
            'edition_id' => (string) $edition->id,
            'kind' => 'attestation',
            'type' => 'participation',
            'title' => 'Attestation de présence effective',
            'layout' => ['width' => 900, 'height' => 636, 'elements' => [
                ['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 200, 'h' => 30, 'text' => '{name}'],
            ]],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $tpl = DocumentTemplate::where('edition_id', $edition->id)->where('type', 'participation')->first();
    expect($tpl->title)->toBe('Attestation de présence effective');

    // Le concepteur rouvre sur le nom enregistré, pas sur le libellé du type.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.templates', ['edition_id' => $edition->id, 'kind' => 'attestation', 'type' => 'participation']))
        ->assertOk()
        ->assertSee('Attestation de présence effective', false);
});

it('ne vide pas le nom quand l’enregistrement n’en porte pas', function () {
    // Un enregistrement automatique sans champ nom (ou un nom effacé par mégarde)
    // ne doit pas effacer le libellé déjà choisi.
    $edition = openEdition();
    $base = [
        'edition_id' => (string) $edition->id, 'kind' => 'attestation', 'type' => 'participation',
        'layout' => ['width' => 900, 'height' => 636, 'elements' => [
            ['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 200, 'h' => 30, 'text' => '{name}'],
        ]],
    ];

    $this->actingAs(adminStaff(), 'web')->postJson(route('admin.documents.templates.save'), $base + ['title' => 'Mon modèle']);
    $this->actingAs(adminStaff(), 'web')->postJson(route('admin.documents.templates.save'), $base + ['title' => '  ']);

    $tpl = DocumentTemplate::where('edition_id', $edition->id)->where('type', 'participation')->first();
    expect($tpl->title)->toBe('Mon modèle');
});

/**
 * ⚠️ Les images du concepteur étaient embarquées en `data:` base64 : elles
 * restaient visibles dans le concepteur (l'instantané les conserve) mais
 * `LayoutSanitizer` refuse `data:`, donc elles étaient jetées de la mise en page
 * enregistrée - les logos disparaissaient du document délivré, en silence.
 */
it('téléverse une image du concepteur et n’enregistre que son adresse', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.documents.templates.upload'), [
            'file' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])
        ->assertOk()
        ->assertJsonPath('ok', true);

    expect(Storage::disk('public')->files('documents/templates'))->toHaveCount(1);
});

it('conserve les images et le fond de page à l’enregistrement', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.documents.templates.save'), [
            'edition_id' => $edition->id,
            'kind' => 'attestation',
            'type' => 'participation',
            'layout' => [
                'width' => 900, 'height' => 636, 'bg' => '#ffffff',
                'bg_image' => '/storage/documents/templates/fond.png',
                'elements' => [
                    ['type' => 'image', 'x' => 40, 'y' => 20, 'w' => 160, 'h' => 60, 'src' => '/storage/documents/templates/logo.png'],
                    // Une image embarquée reste refusée : un document n'a pas à
                    // porter ses octets, et dompdf veut un fichier.
                    ['type' => 'image', 'x' => 10, 'y' => 10, 'w' => 50, 'h' => 50, 'src' => 'data:image/png;base64,AAAA'],
                ],
            ],
        ])
        ->assertOk();

    $layout = (array) DocumentTemplate::where('edition_id', $edition->id)->where('type', 'participation')->value('layout');

    expect($layout['bg_image'])->toBe('/storage/documents/templates/fond.png')
        ->and(collect($layout['elements'])->where('type', 'image')->pluck('src')->all())
        ->toBe(['/storage/documents/templates/logo.png', '']);
});

it('donne à dompdf le fichier de l’image, jamais son adresse distante', function () {
    // dompdf ne télécharge aucune image distante : une adresse du site sortait
    // du PDF alors qu'elle s'affichait bien sur la page.
    $layout = [
        'width' => 400, 'height' => 200, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [['type' => 'image', 'x' => 0, 'y' => 0, 'w' => 100, 'h' => 50, 'src' => '/img/logo-uk.png']],
    ];

    expect(LayoutRenderer::render($layout, [], pdf: true))
        ->toContain(public_path('img/logo-uk.png'))
        ->and(LayoutRenderer::render($layout))
        ->toContain('src="/img/logo-uk.png"');
});
