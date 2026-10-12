<?php

use App\Documents\DocumentFonts;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

it('assainit une mise en page : borne, liste blanche, replis', function () {
    $raw = [
        'width' => 999999, 'height' => -5, 'bg' => 'not-a-color', 'accent' => '#ffd700',
        'elements' => [
            ['type' => 'text', 'x' => -50, 'y' => 100000, 'w' => 200, 'h' => 30, 'text' => 'Bonjour {name}', 'size' => 9000, 'weight' => 50, 'color' => 'red', 'align' => 'middle'],
            ['type' => 'script', 'x' => 0, 'y' => 0], // type interdit → ignoré
            ['type' => 'qr', 'x' => 10, 'y' => 10, 'size' => 5000, 'value' => '{ref}'],
        ],
    ];

    $s = LayoutSanitizer::sanitize($raw, 'badge');

    expect($s['width'])->toBeLessThanOrEqual(2000);
    expect($s['height'])->toBeGreaterThanOrEqual(120);   // repli sur le défaut
    expect($s['bg'])->toBe('#ffffff');                    // couleur invalide → défaut
    expect($s['accent'])->toBe('#ffd700');
    expect($s['elements'])->toHaveCount(2);               // le type interdit est retiré

    $text = collect($s['elements'])->firstWhere('type', 'text');
    expect($text['x'])->toBe(0);                          // borné à [0, width]
    expect($text['size'])->toBeLessThanOrEqual(140);
    expect($text['weight'])->toBeGreaterThanOrEqual(300);
    expect($text['align'])->toBe('left');                 // valeur invalide → défaut

    $qr = collect($s['elements'])->firstWhere('type', 'qr');
    expect($qr['size'])->toBeLessThanOrEqual(400);
});

it('retombe sur la mise en page par défaut si aucun élément valide', function () {
    $s = LayoutSanitizer::sanitize(['elements' => []], 'attestation');

    expect($s['width'])->toBe(900);
    expect(count($s['elements']))->toBeGreaterThan(0);
});

it('rend la mise en page avec substitution des variables', function () {
    $layout = LayoutSanitizer::default('badge');

    $html = LayoutRenderer::render($layout, [
        'org' => 'Université de Kara', 'event' => 'UK 2026', 'name' => 'Awa Kodjo', 'role' => 'Communicant', 'ref' => 'UK-001',
    ]);

    expect($html)->toContain('Awa Kodjo');
    expect($html)->toContain('Communicant');
    expect($html)->toContain('<svg');           // QR rendu
    expect($html)->not->toContain('{name}');     // variable substituée
});

it('échappe le texte utilisateur dans le rendu', function () {
    $layout = LayoutSanitizer::sanitize([
        'elements' => [['type' => 'text', 'x' => 0, 'y' => 0, 'w' => 100, 'h' => 20, 'text' => '{name}']],
    ], 'badge');

    $html = LayoutRenderer::render($layout, ['name' => '<script>alert(1)</script>']);

    expect($html)->not->toContain('<script>alert(1)</script>');
    expect($html)->toContain('&lt;script&gt;');
});

it('conserve la mise en forme dessinée dans le concepteur', function () {
    // ⚠️ Le défaut d'origine : le modèle enregistré ne retenait que la position,
    // la taille, la graisse et l'alignement. Un titre dessiné en Playfair
    // italique souligné ressortait en Jost droit - le document délivré ne
    // ressemblait plus à la maquette.
    $raw = [
        'width' => 900, 'height' => 636, 'bg' => '#fdf6e3',
        'elements' => [
            [
                'type' => 'text', 'x' => 60, 'y' => 150, 'w' => 780, 'h' => 50,
                'text' => 'Attestation', 'size' => 34, 'weight' => 900, 'align' => 'center',
                'font' => 'playfair', 'italic' => true, 'underline' => true,
                'upper' => true, 'leading' => 1.5, 'spacing' => 2.5,
            ],
            ['type' => 'rect', 'x' => 40, 'y' => 40, 'w' => 820, 'h' => 556, 'color' => '#ffffff', 'radius' => 18, 'border' => 3, 'border_color' => '#223ea4'],
        ],
    ];

    $s = LayoutSanitizer::sanitize($raw, 'attestation');

    expect($s['bg'])->toBe('#fdf6e3');  // le fond de page n'est plus forcé au blanc

    $text = collect($s['elements'])->firstWhere('type', 'text');
    expect($text['font'])->toBe('playfair');
    expect($text['italic'])->toBeTrue();
    expect($text['underline'])->toBeTrue();
    expect($text['upper'])->toBeTrue();
    expect($text['leading'])->toBe(1.5);
    expect($text['spacing'])->toBe(2.5);

    $rect = collect($s['elements'])->firstWhere('type', 'rect');
    expect($rect['radius'])->toBe(18);
    expect($rect['border'])->toBe(3);

    // Et le rendu applique réellement ce qui a été retenu.
    $html = LayoutRenderer::render($s, []);
    expect($html)->toContain('Playfair Display');
    expect($html)->toContain('font-style:italic');
    expect($html)->toContain('text-decoration:underline');
    expect($html)->toContain('text-transform:uppercase');
    expect($html)->toContain('letter-spacing:2.50px');
    expect($html)->toContain('line-height:1.50');
    expect($html)->toContain('border-radius:18px');
    expect($html)->toContain('border:3px solid #223ea4');
    expect($html)->toContain('background:#fdf6e3');
});

it('refuse une police inconnue et retombe sur Jost', function () {
    // La valeur enregistrée est une CLÉ : une déclaration CSS libre finirait
    // telle quelle dans l'attribut `style` du document.
    $s = LayoutSanitizer::sanitize([
        'elements' => [['type' => 'text', 'x' => 0, 'y' => 0, 'text' => 'X', 'font' => 'red;}body{display:none']],
    ], 'attestation');

    expect(collect($s['elements'])->firstWhere('type', 'text')['font'])->toBe('jost');
});

it('accepte une image téléversée servie par le site', function () {
    // ⚠️ Seules les URL absolues passaient : un logo référencé en /storage/…
    // était effacé à l'enregistrement et disparaissait du document.
    $s = LayoutSanitizer::sanitize([
        'elements' => [
            ['type' => 'image', 'x' => 0, 'y' => 0, 'w' => 80, 'h' => 80, 'src' => '/storage/branding/logo.png'],
            ['type' => 'image', 'x' => 0, 'y' => 0, 'w' => 80, 'h' => 80, 'src' => 'data:image/png;base64,AAAA'],
        ],
    ], 'attestation');

    $images = collect($s['elements'])->where('type', 'image')->values();
    expect($images[0]['src'])->toBe('/storage/branding/logo.png');
    expect($images[1]['src'])->toBe('');   // data: refusé
});

it('remplit les jetons de la palette du concepteur', function () {
    // ⚠️ La palette propose `[espace.cle]` ; les contrôleurs ne fournissaient que
    // la forme plate `{cle}`. Un modèle dessiné avec la palette sortait donc avec
    // ses jetons vides - ou affichés tels quels pour ceux contenant un souligné,
    // que l'expression régulière ne reconnaissait pas.
    $layout = LayoutSanitizer::sanitize([
        'elements' => [
            ['type' => 'text', 'x' => 0, 'y' => 0, 'w' => 600, 'h' => 30, 'text' => '[recipient.name] - [certificate.issued_on] - [edition.theme] - [certificate.uuid]'],
        ],
    ], 'attestation');

    $html = LayoutRenderer::render($layout, [
        'name' => 'Ama Doe',
        'date' => '12/10/2026',
        'theme' => 'Innover pour un avenir durable',
        'ref' => 'abc-123',
    ]);

    expect($html)->toContain('Ama Doe');
    expect($html)->toContain('12/10/2026');
    expect($html)->toContain('Innover pour un avenir durable');
    expect($html)->toContain('abc-123');
    expect($html)->not->toContain('[certificate.issued_on]');  // le souligné était le piège
    expect($html)->not->toContain('[recipient.name]');
});

/**
 * ⚠️ **dompdf ne garde que quatre variantes par famille** (normale, grasse,
 * italique, grasse italique) : les six graisses de Jost s'y écrasaient deux à
 * deux, et un texte dessiné en 500 ressortait en 400, un titre en 900 en 700.
 * Chaque fonte a donc sa propre famille dans le PDF.
 */
it('garde la graisse exacte du concepteur dans le PDF', function () {
    expect(DocumentFonts::pdfFamily('jost', 500, false))->toBe('Jost 500')
        ->and(DocumentFonts::pdfFamily('jost', 900, false))->toBe('Jost 900')
        ->and(DocumentFonts::pdfFamily('jost', 400, true))->toBe('Jost 400 Italic')
        ->and(DocumentFonts::pdfFamily('playfair', 900, false))->toBe('Playfair Display 900')
        // Georgia, Arial, Lato ne sont pas hébergées : rien à viser.
        ->and(DocumentFonts::pdfFamily('georgia', 400, false))->toBeNull();

    $faces = view('documents._fonts', ['pdf' => true])->render();
    expect($faces)->toContain("font-family: 'Jost 500'")
        ->and($faces)->toContain("font-family: 'Playfair Display 900'");
});

it('demande la fonte par son nom, et non par une graisse que dompdf arrondirait', function () {
    $layout = [
        'width' => 400, 'height' => 200, 'bg' => '#fff', 'accent' => '#223ea4',
        'elements' => [[
            'type' => 'text', 'x' => 10, 'y' => 10, 'w' => 300, 'h' => 40,
            'text' => 'Titre', 'size' => 28, 'weight' => 900, 'font' => 'playfair',
        ]],
    ];

    $pdf = LayoutRenderer::render($layout, [], pdf: true);

    // ⚠️ `e()` échapperait l'apostrophe en &#039; et le moteur ne reconnaîtrait
    // plus la famille.
    expect($pdf)->toContain("font-family:'Playfair Display 900'")
        ->and($pdf)->toContain('font-weight:normal')
        ->and($pdf)->not->toContain('&#039;')
        ->and(LayoutRenderer::render($layout))->toContain('font-weight:900');
});
