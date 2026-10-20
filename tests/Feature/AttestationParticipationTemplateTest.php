<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Modèle par défaut de l'attestation de PARTICIPATION (charte UK x Parakou).
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur (template-designer.js)
 * décrivent la MÊME maquette : ce test garde le côté PHP.
 */

// openEdition()/verifiedAccount()/seededSubmission() -> tests/Pest.php

it('a un modèle par défaut propre au type « participation »', function () {
    $part = LayoutSanitizer::default('attestation', 'participation');
    $generic = LayoutSanitizer::default('attestation', 'moderation');

    // Modèle distinct du défaut générique, cadre décoratif officiel (partagé).
    expect($part)->not->toBe($generic);
    expect($part['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    // Jetons de la palette du concepteur ([espace.cle]), civilité comprise.
    $texts = collect($part['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE PARTICIPATION')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->toContain('[edition.location]')
        ->toContain('Thème du colloque :')
        // Les lignes propres à la communication n'existent pas ici.
        ->not->toContain('[communication.title]')
        ->not->toContain('[communication.type]');

    // Deux logos en éléments image (UK des Réglages + asset Parakou).
    $images = collect($part['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();

    // QR de vérification + titre en Times (taille du modèle).
    $qr = collect($part['elements'])->firstWhere('type', 'qr');
    expect($qr)->not->toBeNull();
    $title = collect($part['elements'])->firstWhere('text', 'ATTESTATION DE PARTICIPATION');
    expect($title['font'])->toBe('times');
});

it('rend la vraie civilité et une date en toutes lettres', function () {
    $submission = seededSubmission('accepted');
    $submission->participant->user->update(['civility' => 'Mme']);
    $attestation = app(IssueAttestation::class)->handle($submission->participant, 'participation');

    $layout = LayoutSanitizer::default('attestation', 'participation');
    $html = LayoutRenderer::render($layout, AttestationVars::for($attestation, 'https://example.test'));

    expect($html)
        ->toContain('ATTESTATION DE PARTICIPATION')
        ->toContain('Mme Awa KODJO')
        ->not->toContain('Mme/M.');

    // Date du « Fait à … » en toutes lettres, jamais 12/10/2026.
    $longDate = $attestation->issued_at->locale('fr')->translatedFormat('j F Y');
    expect($html)
        ->toContain($longDate)
        ->not->toContain($attestation->issued_at->format('d/m/Y'));
});
