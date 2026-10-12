<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\DocumentVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Modèle par défaut de l'attestation de COMMUNICATION (charte UK x Parakou) et
 * jeton {communication_type} (format de présentation : orale / affichée).
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur (template-designer.js)
 * décrivent la MÊME maquette : ce test garde le côté PHP.
 */

// openEdition()/verifiedAccount()/seededSubmission() -> tests/Pest.php

it('a un modèle par défaut propre au type « communication »', function () {
    $comm = LayoutSanitizer::default('attestation', 'communication');
    $default = LayoutSanitizer::default('attestation', 'participation');

    // Un modèle distinct du défaut générique, avec son cadre décoratif officiel.
    expect($comm)->not->toBe($default);
    expect($comm['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    $texts = collect($comm['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE COMMUNICATION')
        ->toContain('{communication}')
        ->toContain('{communication_type}')
        ->toContain('Type de communication :');
});

it('fige le format de présentation à l’émission (orale / affichée)', function () {
    $submission = seededSubmission('accepted');
    $submission->update(['type' => 'poster']);
    $participant = $submission->participant;

    $attestation = app(IssueAttestation::class)->handle($participant, 'communication', $submission->fresh());

    expect($attestation->meta['communication_type'] ?? null)->toBe('Affichée');
    expect(AttestationVars::for($attestation, 'https://example.test')['communication_type'])->toBe('Affichée');
});

it('rend {communication_type} et {communication} sur l’attestation', function () {
    $submission = seededSubmission('accepted'); // type « oral » par défaut
    $submission->update(['title' => 'Pile microbienne à plante de citronnelle']);
    $attestation = app(IssueAttestation::class)->handle($submission->participant, 'communication', $submission->fresh());

    $layout = LayoutSanitizer::default('attestation', 'communication');
    $html = LayoutRenderer::render($layout, AttestationVars::for($attestation, 'https://example.test'));

    expect($html)
        ->toContain('Orale')
        ->toContain('Pile microbienne à plante de citronnelle')
        ->toContain('Mme/M. Awa Kodjo');
});

it('résout l’alias [communication.type] vers le format, pas la référence', function () {
    $vars = DocumentVars::expand(['communication_type' => 'Orale', 'communication_ref' => 'CI-007']);

    expect($vars['communication.type'])->toBe('Orale');
    expect($vars['communication.ref'])->toBe('CI-007');
});
