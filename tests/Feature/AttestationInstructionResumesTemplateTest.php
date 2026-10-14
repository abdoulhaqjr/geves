<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;

/**
 * Modèle par défaut de l'attestation d'INSTRUCTION DE RÉSUMÉS (charte UK x Parakou),
 * calquée sur modération / rapporteur via la maquette officielle partagée.
 *
 * ⚠️ Le défaut PHP (rendu) et le modèle du concepteur (template-designer.js)
 * décrivent la MÊME maquette : ce test garde le côté PHP.
 */

// openEdition()/verifiedAccount()/seededSubmission() -> tests/Pest.php

it('a un modèle officiel propre au type « instruction de résumés »', function () {
    $layout = LayoutSanitizer::default('attestation', 'instruction_resumes');

    // Même cadre officiel que modération / rapporteur.
    expect($layout['bg_image'])->toBe('/img/attestations/attestation-communication-bg.png');

    $texts = collect($layout['elements'])->pluck('text')->implode(' | ');
    expect($texts)
        ->toContain('ATTESTATION D’INSTRUCTION DE RÉSUMÉS')
        ->toContain('évaluation, la sélection et l’annotation des résumés')
        ->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->toContain('Thème du colloque :')
        ->not->toContain('[communication.title]');

    $images = collect($layout['elements'])->where('type', 'image')->pluck('src');
    expect($images)->toHaveCount(2);
    expect($images->contains('/img/attestations/logo-parakou.png'))->toBeTrue();
    expect($images->contains(fn ($s) => str_contains((string) $s, 'logo-uk')))->toBeTrue();

    $qr = collect($layout['elements'])->firstWhere('type', 'qr');
    expect($qr)->not->toBeNull();
    $title = collect($layout['elements'])->firstWhere('text', 'ATTESTATION D’INSTRUCTION DE RÉSUMÉS');
    expect($title['font'])->toBe('times');
});

it('rend la vraie civilité et une date en toutes lettres', function () {
    $submission = seededSubmission('accepted');
    $submission->participant->user->update(['civility' => 'Mme']);
    $attestation = app(IssueAttestation::class)->handle($submission->participant, 'instruction_resumes');

    $layout = LayoutSanitizer::default('attestation', 'instruction_resumes');
    $html = LayoutRenderer::render($layout, AttestationVars::for($attestation, 'https://example.test'));

    expect($html)
        ->toContain('ATTESTATION D’INSTRUCTION DE RÉSUMÉS')
        ->toContain('Mme Awa Kodjo')
        ->not->toContain('Mme/M.');

    $longDate = $attestation->issued_at->locale('fr')->translatedFormat('j F Y');
    expect($html)->toContain($longDate)->not->toContain($attestation->issued_at->format('d/m/Y'));
});
