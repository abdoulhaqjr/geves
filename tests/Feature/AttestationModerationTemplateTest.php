<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Modèle par défaut de l'attestation de MODÉRATION (charte UK x Parakou).
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur (template-designer.js)
 * décrivent la MÊME maquette : ce test garde le côté PHP.
 */

// openEdition()/verifiedAccount()/seededSubmission() -> tests/Pest.php

it('a un modèle par défaut propre au type « modération »', function () {
    $mod = LayoutSanitizer::default('attestation', 'moderation');
    $generic = LayoutSanitizer::default('attestation', 'rapporteur');

    expect($mod)->not->toBe($generic);
    expect($mod['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    $texts = collect($mod['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE MODÉRATION')
        ->toContain('pour avoir modéré une session')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->toContain('[edition.location]')
        ->toContain('Thème du colloque :')
        ->not->toContain('[communication.title]');

    $images = collect($mod['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();

    $qr = collect($mod['elements'])->firstWhere('type', 'qr');
    expect($qr)->not->toBeNull();
    $title = collect($mod['elements'])->firstWhere('text', 'ATTESTATION DE MODÉRATION');
    expect($title['font'])->toBe('times');
});

it('rend la vraie civilité et une date en toutes lettres', function () {
    $submission = seededSubmission('accepted');
    $submission->participant->user->update(['civility' => 'Mme']);
    $attestation = app(IssueAttestation::class)->handle($submission->participant, 'moderation');

    $layout = LayoutSanitizer::default('attestation', 'moderation');
    $html = LayoutRenderer::render($layout, AttestationVars::for($attestation, 'https://example.test'));

    expect($html)
        ->toContain('ATTESTATION DE MODÉRATION')
        ->toContain('Mme Awa Kodjo')
        ->not->toContain('Mme/M.');

    $longDate = $attestation->issued_at->locale('fr')->translatedFormat('j F Y');
    expect($html)->toContain($longDate)->not->toContain($attestation->issued_at->format('d/m/Y'));
});
