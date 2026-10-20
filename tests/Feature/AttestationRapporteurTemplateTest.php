<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Modèle par défaut de l'attestation de RAPPORTEUR (charte UK x Parakou).
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur (template-designer.js)
 * décrivent la MÊME maquette : ce test garde le côté PHP.
 */

// openEdition()/verifiedAccount()/seededSubmission() -> tests/Pest.php

it('a un modèle par défaut propre au type « rapporteur »', function () {
    $rap = LayoutSanitizer::default('attestation', 'rapporteur');
    $generic = LayoutSanitizer::default('attestation', 'formation');

    expect($rap)->not->toBe($generic);
    expect($rap['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    $texts = collect($rap['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE RAPPORTEUR')
        ->toContain('pour avoir servi en tant que rapporteur')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->toContain('[edition.location]')
        ->toContain('Thème du colloque :')
        ->not->toContain('[communication.title]');

    $images = collect($rap['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();

    $qr = collect($rap['elements'])->firstWhere('type', 'qr');
    expect($qr)->not->toBeNull();
    $title = collect($rap['elements'])->firstWhere('text', 'ATTESTATION DE RAPPORTEUR');
    expect($title['font'])->toBe('times');
});

it('rend la vraie civilité et une date en toutes lettres', function () {
    $submission = seededSubmission('accepted');
    $submission->participant->user->update(['civility' => 'Mme']);
    $attestation = app(IssueAttestation::class)->handle($submission->participant, 'rapporteur');

    $layout = LayoutSanitizer::default('attestation', 'rapporteur');
    $html = LayoutRenderer::render($layout, AttestationVars::for($attestation, 'https://example.test'));

    expect($html)
        ->toContain('ATTESTATION DE RAPPORTEUR')
        ->toContain('Mme Awa KODJO')
        ->not->toContain('Mme/M.');

    $longDate = $attestation->issued_at->locale('fr')->translatedFormat('j F Y');
    expect($html)->toContain($longDate)->not->toContain($attestation->issued_at->format('d/m/Y'));
});
