<?php

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Badge par défaut, calqué sur le badge officiel du comité d'organisation
 * (UK x Université de Parakou).
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur de badges
 * (badge-designer.js, modèle `co`) décrivent la MÊME maquette : ce test garde
 * le côté PHP.
 */
it('a un badge par défaut calqué sur le comité d’organisation', function () {
    $layout = LayoutSanitizer::default('badge');

    // A6 portrait + cadre décoratif vert/bleu en image de fond.
    expect($layout['width'])->toBe(397);
    expect($layout['height'])->toBe(560);
    expect($layout['bg_image'])->toBe('/img/badges/badge-default-bg.png');

    $texts = collect($layout['elements'])->where('type', 'text')->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('{event}')
        ->toContain('{name}')
        ->toContain('{role}')
        ->toContain('{theme}')
        ->toContain('{location}')
        ->toContain('{dates}');

    // Deux logos OFFICIELS (les mêmes que les attestations : UK large + Parakou large) et un QR.
    $images = collect($layout['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();
    expect(collect($layout['elements'])->firstWhere('type', 'qr'))->not->toBeNull();

    // Deux filets verts (gauche cadre→UK, droite Parakou→cadre ; PAS de segment au
    // milieu). Éléments de mise en page (pas cuits dans le fond) → ils se redimensionnent
    // au changement de format. ⚠️ Couleur #00be62 = la MÊME que le cadre (contour).
    $rects = collect($layout['elements'])->where('type', 'rect');
    expect($rects)->toHaveCount(2);
    expect($rects->every(fn ($r) => strtolower((string) $r['color']) === '#00be62'))->toBeTrue();
});

it('rend le badge par défaut avec les valeurs du participant', function () {
    $layout = LayoutSanitizer::default('badge');
    $html = LayoutRenderer::render($layout, [
        'event' => '6e Colloque Scientifique International',
        'name' => 'M. Assogba Gervais',
        'role' => 'Participant',
        'theme' => 'Les métiers d’aujourd’hui et de demain',
        'location' => 'Palais des congrès de Kara',
        'dates' => 'Du 06 au 10 octobre 2025',
        'ref' => 'UKC-2026-001',
    ]);

    expect($html)
        ->toContain('M. Assogba Gervais')
        ->toContain('Participant')
        ->toContain('Palais des congrès de Kara')
        ->toContain('Du 06 au 10 octobre 2025')
        ->toContain('/img/badges/badge-default-bg.png');
});
