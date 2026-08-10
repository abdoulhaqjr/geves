<?php

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
