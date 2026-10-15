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

    // Jetons de la palette du concepteur ([espace.cle]), pas la forme plate.
    $texts = collect($layout['elements'])->where('type', 'text')->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('[edition.name]')
        ->toContain('[recipient.name]')
        ->toContain('[recipient.role]')
        ->toContain('[edition.theme]')
        ->toContain('[edition.location]')
        ->toContain('[edition.dates]');

    // Les deux logos OFFICIELS (UK large + Parakou large) sont présents, parmi les
    // images (les pictogrammes lieu/dates en sont aussi) ; plus un QR.
    $images = collect($layout['elements'])->where('type', 'image')->pluck('src');
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();
    expect(collect($layout['elements'])->firstWhere('type', 'qr'))->not->toBeNull();

    // ⚠️ Les filets verts sont CUITS DANS LE CADRE de l'image de fond (soudés au contour,
    // sans fente), donc PAS d'élément `rect` dans la mise en page. Ne pas les repasser en
    // rect : un filet-élément laissait un jour contre le cadre dans le concepteur.
    expect(collect($layout['elements'])->where('type', 'rect'))->toHaveCount(0);
});

it('a un modèle « Participant avec photo » : même maquette + une photo entre l’événement et le nom', function () {
    $base = LayoutSanitizer::default('badge');
    $photo = LayoutSanitizer::default('badge', 'participant_photo');

    // Même cadre, même taille que le badge participant.
    expect($photo['width'])->toBe(397);
    expect($photo['height'])->toBe(560);
    expect($photo['bg_image'])->toBe($base['bg_image']);

    // Un (et un seul) élément photo, rond, rempli par recipient.photo au rendu.
    $photos = collect($photo['elements'])->where('type', 'photo');
    expect($photos)->toHaveCount(1);
    expect(collect($base['elements'])->where('type', 'photo'))->toHaveCount(0);

    $yOf = fn (array $layout, string $token) => collect($layout['elements'])
        ->firstWhere('text', $token)['y'] ?? null;

    // L'événement REMONTE, le nom DESCEND, et la photo s'intercale entre les deux.
    expect($yOf($photo, '[edition.name]'))->toBeLessThan($yOf($base, '[edition.name]'));
    expect($yOf($photo, '[recipient.name]'))->toBeGreaterThan($yOf($base, '[recipient.name]'));
    $photoY = $photos->first()['y'];
    expect($photoY)->toBeGreaterThan($yOf($photo, '[edition.name]'));
    expect($photoY)->toBeLessThan($yOf($photo, '[recipient.name]'));
});

it('rend le badge avec photo en utilisant recipient.photo', function () {
    $layout = LayoutSanitizer::default('badge', 'participant_photo');
    $html = LayoutRenderer::render($layout, [
        'recipient.name' => 'Awa Kodjo',
        'recipient.role' => 'Participant',
        'recipient.photo' => '/img/badges/icon-location.png',
    ]);

    expect($html)
        ->toContain('Awa Kodjo')
        ->toContain('Participant')
        ->toContain('/img/badges/icon-location.png'); // la photo est bien insérée
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
