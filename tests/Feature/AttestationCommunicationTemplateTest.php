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

    // Jetons de la palette du concepteur ([espace.cle]), civilité comprise.
    $texts = collect($comm['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION DE COMMUNICATION')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[communication.title]')
        ->toContain('[communication.type]')
        ->toContain('[edition.location]')
        ->toContain('Type de communication :');

    // Les deux logos sont des éléments IMAGE (pas cuits dans le fond) : l'UK suit
    // le logo large des Réglages, Parakou est l'asset livré.
    $images = collect($comm['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();

    // QR de vérification + titre en Times (taille du modèle).
    $qr = collect($comm['elements'])->firstWhere('type', 'qr');
    expect($qr)->not->toBeNull();
    $title = collect($comm['elements'])->firstWhere('text', 'ATTESTATION DE COMMUNICATION');
    expect($title['font'])->toBe('times');
});

it('fige le format de présentation à l’émission (orale / affichée)', function () {
    $submission = seededSubmission('accepted');
    $submission->update(['type' => 'poster']);
    $participant = $submission->participant;

    $attestation = app(IssueAttestation::class)->handle($participant, 'communication', $submission->fresh());

    expect($attestation->meta['communication_type'] ?? null)->toBe('Affichée');
    expect(AttestationVars::for($attestation, 'https://example.test')['communication_type'])->toBe('Affichée');
});

it('rend le format, la communication et la vraie civilité sur l’attestation', function () {
    $submission = seededSubmission('accepted'); // type « oral » par défaut
    $submission->update(['title' => 'Pile microbienne à plante de citronnelle']);
    // Civilité réelle du bénéficiaire (plus de « Mme/M. » générique).
    $submission->participant->user->update(['civility' => 'Mme']);
    $attestation = app(IssueAttestation::class)->handle($submission->participant, 'communication', $submission->fresh());

    $layout = LayoutSanitizer::default('attestation', 'communication');
    $html = LayoutRenderer::render($layout, AttestationVars::for($attestation, 'https://example.test'));

    expect($html)
        ->toContain('Orale')
        ->toContain('Pile microbienne à plante de citronnelle')
        ->toContain('Mme Awa KODJO')
        ->not->toContain('Mme/M.');

    // Date du « Fait à … » en toutes lettres (« 12 octobre 2026 »), jamais 12/10/2026.
    $longDate = $attestation->issued_at->locale('fr')->translatedFormat('j F Y');
    expect($html)
        ->toContain($longDate)
        ->not->toContain($attestation->issued_at->format('d/m/Y'));
});

it('résout l’alias [communication.type] vers le format, pas la référence', function () {
    $vars = DocumentVars::expand(['communication_type' => 'Orale', 'communication_ref' => 'CI-007']);

    expect($vars['communication.type'])->toBe('Orale');
    expect($vars['communication.ref'])->toBe('CI-007');
});
