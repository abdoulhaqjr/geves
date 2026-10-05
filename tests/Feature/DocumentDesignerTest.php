<?php

use App\Actions\IssueAttestation;
use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\EventSeries;
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
    $event = openEdition();

    $resp = $this->actingAs($staff, 'web')->postJson(route('admin.documents.templates.generate'), [
        'event_id' => (string) $event->id,
        'kind' => 'badge',
        'type' => 'default',
        'prompt' => 'sobre, bleu UK',
    ])->assertOk();

    expect(json_encode($resp->json('layout')))->toContain('GEN');
});

it('enregistre le modèle', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->postJson(route('admin.documents.templates.save'), [
        'event_id' => (string) $event->id,
        'kind' => 'attestation',
        'type' => 'communication',
        'layout' => ['width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4', 'elements' => []],
    ])->assertOk()->assertJsonPath('ok', true);

    expect(DocumentTemplate::where('event_id', $event->id)->where('kind', 'attestation')->where('type', 'communication')->exists())->toBeTrue();
});

it('enregistre la mise en page de l’éditeur visuel (sanitisée)', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')->postJson(route('admin.documents.templates.save'), [
        'event_id' => (string) $event->id,
        'kind' => 'badge',
        'layout' => [
            'width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'text', 'x' => 5000, 'y' => 10, 'w' => 300, 'h' => 30, 'text' => 'EDIT {name}', 'size' => 20, 'weight' => 700, 'color' => '#111111', 'align' => 'center'],
                ['type' => 'chaos', 'x' => 0, 'y' => 0], // type non listé → ignoré
            ],
        ],
    ])->assertOk();

    $tpl = DocumentTemplate::where('event_id', $event->id)->where('kind', 'badge')->where('type', 'default')->first();

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

it('rend l’attestation via le modèle de l’événement si présent', function () {
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $att = (new IssueAttestation)->handle($p, 'participation');

    DocumentTemplate::create([
        'event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation',
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
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'status' => 'open', 'slug' => 'uk-2026']);
    $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    DocumentTemplate::create([
        'event_id' => $event->id, 'kind' => 'badge', 'type' => 'default',
        'layout' => ['width' => 320, 'height' => 440, 'bg' => '#fff', 'accent' => '#223ea4',
            'elements' => [['type' => 'text', 'x' => 10, 'y' => 200, 'w' => 300, 'h' => 30, 'text' => 'CARTE {name}']]],
    ]);

    $this->actingAs($staff, 'web')->get(route('admin.documents.badges', $event))
        ->assertOk()
        ->assertSee('CARTE Awa Kodjo');
});

it('enregistre le nom saisi dans le concepteur', function () {
    // ⚠️ Le nom était affiché et modifiable dans l'en-tête, mais ni transmis ni
    // enregistré : renommer n'avait aucun effet, alors que le message de
    // confirmation citait le nouveau nom.
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.documents.templates.save'), [
            'event_id' => (string) $event->id,
            'kind' => 'attestation',
            'type' => 'participation',
            'title' => 'Attestation de présence effective',
            'layout' => ['width' => 900, 'height' => 636, 'elements' => [
                ['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 200, 'h' => 30, 'text' => '{name}'],
            ]],
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $tpl = DocumentTemplate::where('event_id', $event->id)->where('type', 'participation')->first();
    expect($tpl->title)->toBe('Attestation de présence effective');

    // Le concepteur rouvre sur le nom enregistré, pas sur le libellé du type.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.templates', ['event_id' => $event->id, 'kind' => 'attestation', 'type' => 'participation']))
        ->assertOk()
        ->assertSee('Attestation de présence effective', false);
});

it('ne vide pas le nom quand l’enregistrement n’en porte pas', function () {
    // Un enregistrement automatique sans champ nom (ou un nom effacé par mégarde)
    // ne doit pas effacer le libellé déjà choisi.
    $event = openEdition();
    $base = [
        'event_id' => (string) $event->id, 'kind' => 'attestation', 'type' => 'participation',
        'layout' => ['width' => 900, 'height' => 636, 'elements' => [
            ['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 200, 'h' => 30, 'text' => '{name}'],
        ]],
    ];

    $this->actingAs(adminStaff(), 'web')->postJson(route('admin.documents.templates.save'), $base + ['title' => 'Mon modèle']);
    $this->actingAs(adminStaff(), 'web')->postJson(route('admin.documents.templates.save'), $base + ['title' => '  ']);

    $tpl = DocumentTemplate::where('event_id', $event->id)->where('type', 'participation')->first();
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
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.documents.templates.save'), [
            'event_id' => $event->id,
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

    $layout = (array) DocumentTemplate::where('event_id', $event->id)->where('type', 'participation')->value('layout');

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

/**
 * ⚠️ Le QR de vérification encodait une adresse FIXE et sans identifiant
 * (« …/attestation-verify ») : scanner deux attestations différentes menait au
 * même endroit, qui ne vérifiait rien. Il encode le jeton, que le rendu
 * remplace par l'adresse de l'attestation délivrée.
 */
it('n’encode jamais une adresse de vérification figée dans le concepteur', function () {
    $js = file_get_contents(resource_path('js/template-designer.js'));

    // Le mot peut rester dans un commentaire : c'est l'ADRESSE codée en dur
    // qu'on interdit.
    expect(preg_match('#https?://[^\'"\s]*attestation-verify#', $js))->toBe(0)
        ->and($js)->toContain("var DEFAULT_QR_URL = '{verify_url}';");
});

it('le QR rendu porte l’adresse de l’attestation, pas un lien générique', function () {
    $layout = [
        'width' => 400, 'height' => 300, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [['type' => 'qr', 'x' => 10, 'y' => 10, 'size' => 80, 'value' => '{verify_url}']],
    ];

    // Le QR est un SVG : on vérifie que le jeton a bien été substitué avant
    // encodage, en comparant deux adresses différentes.
    $a = LayoutRenderer::render($layout, ['verify_url' => 'https://exemple.test/attestation/aaa']);
    $b = LayoutRenderer::render($layout, ['verify_url' => 'https://exemple.test/attestation/bbb']);

    expect($a)->not->toBe($b);
});

it('dessine le fond de page du modèle sur le document', function () {
    // ⚠️ Le fond n'était pas enregistré : une attestation dessinée sur un fond
    // illustré ressortait sur du blanc.
    $layout = LayoutSanitizer::sanitize([
        'width' => 900, 'height' => 636, 'bg' => '#ffffff',
        'bg_image' => '/img/bg-attestation-uk.png',
        'elements' => [['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 100, 'h' => 20, 'text' => 'X']],
    ], 'attestation', 'participation');

    expect($layout['bg_image'])->toBe('/img/bg-attestation-uk.png')
        ->and(LayoutRenderer::render($layout))->toContain('/img/bg-attestation-uk.png');
});

/**
 * ⚠️ dompdf n'applique pas `line-height` au corps du texte : il le multiplie par
 * la hauteur propre de la police. Avec Jost (1,445 em), l'interligne sortait
 * 59 % trop grand et un paragraphe de deux lignes perdait la seconde, coupée par
 * sa boîte. Le PDF compense ; la page, elle, garde la valeur du concepteur.
 */
it('compense l’interligne de dompdf, et seulement pour le PDF', function () {
    $layout = [
        'width' => 400, 'height' => 200, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [[
            'type' => 'text', 'x' => 10, 'y' => 10, 'w' => 300, 'h' => 40,
            'text' => 'Texte', 'size' => 13, 'leading' => 1.45, 'font' => 'jost',
        ]],
    ];

    expect(LayoutRenderer::render($layout))->toContain('line-height:1.45')
        // 13 x 1,45 / 1,5895 - le pas obtenu vaut alors 13 x 1,45 px à l'écran.
        ->and(LayoutRenderer::render($layout, [], pdf: true))->toContain('line-height:11.86px');
});

it('ne rogne pas le texte dans le PDF', function () {
    // Une ligne de trop était coupée sans un mot ; mieux vaut qu'elle déborde.
    $layout = [
        'width' => 400, 'height' => 200, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 300, 'h' => 20, 'text' => 'Texte']],
    ];

    // La page, elle, borne toujours son contenu : c'est le bloc de TEXTE qui ne
    // doit plus rogner.
    expect(substr_count(LayoutRenderer::render($layout, [], pdf: true), 'overflow:hidden'))->toBe(1)
        ->and(substr_count(LayoutRenderer::render($layout), 'overflow:hidden'))->toBe(2);
});

it('met les capitales dans le PDF, que dompdf ne sait pas transformer', function () {
    $layout = [
        'width' => 400, 'height' => 200, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 300, 'h' => 20, 'text' => 'Décernée à', 'upper' => true]],
    ];

    expect(LayoutRenderer::render($layout, [], pdf: true))->toContain('DÉCERNÉE À')
        ->and(LayoutRenderer::render($layout))->toContain('text-transform:uppercase');
});

/**
 * ⚠️ dompdf ne rend pas le SVG : le QR était **absent du PDF téléchargé** alors
 * qu'il s'affiche sur la page. C'est lui qui mène à la vérification - une
 * attestation imprimée sans QR n'est plus vérifiable.
 */
it('dessine le QR dans le PDF, que dompdf ne saurait pas rendre en SVG', function () {
    $layout = [
        'width' => 400, 'height' => 300, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [['type' => 'qr', 'x' => 10, 'y' => 10, 'size' => 80, 'value' => '{verify_url}']],
    ];
    $vars = ['verify_url' => 'https://exemple.test/attestation/aaa'];

    $page = LayoutRenderer::render($layout, $vars);
    $pdf = LayoutRenderer::render($layout, $vars, pdf: true);

    expect($page)->toContain('<svg')
        ->and($pdf)->not->toContain('<svg')
        // Des modules, pas une image : dompdf sait tracer des rectangles.
        ->and(substr_count($pdf, 'background:#000'))->toBeGreaterThan(50);
});
