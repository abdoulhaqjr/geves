<?php

use App\Actions\GenerateDocumentLayout;
use App\AI\Contracts\AiProvider;

// bindFakeAi() -> GenerateReportTest ; adminStaff(), openEdition() -> helpers.

/** Fournisseur IA factice qui CAPTURE la consigne reçue et renvoie une sortie minimale. */
function capturingAiProvider(object $sink): AiProvider
{
    return new class($sink) implements AiProvider
    {
        public function __construct(private object $sink) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            $this->sink->instruction = $instruction;

            // Sortie volontairement vide : le sanitizer retombe sur la maquette par défaut.
            return ['elements' => []];
        }
    };
}

it('décrit au modèle TOUT ce que le concepteur de badge sait rendre', function () {
    $sink = new stdClass;
    $sink->instruction = '';
    bindFakeAi(capturingAiProvider($sink));

    app(GenerateDocumentLayout::class)->handle('badge', 'default', 'Badge participant avec photo ronde');

    $i = $sink->instruction;

    // Types d'éléments riches, dont la photo (spécifique au badge) et les formes avancées.
    expect($i)->toContain('"type":"photo"')
        ->and($i)->toContain('"type":"qr"')
        ->and($i)->toContain('"type":"signatures"')
        ->and($i)->toContain('radius')
        ->and($i)->toContain('border');

    // Mise en forme du texte.
    expect($i)->toContain('upper')->and($i)->toContain('leading')->and($i)->toContain('spacing');

    // Clés de polices de la liste blanche.
    expect($i)->toContain('jost')->and($i)->toContain('times')->and($i)->toContain('lato');

    // Jetons de palette du badge (et pas des valeurs en dur).
    expect($i)->toContain('[recipient.name]')
        ->and($i)->toContain('[edition.theme]')
        ->and($i)->toContain('[edition.location]')
        ->and($i)->toContain('{verify_url}');

    // Assets officiels réels : cadre de fond, logos, pictogrammes.
    expect($i)->toContain('/img/badges/badge-default-bg.png')
        ->and($i)->toContain('/img/attestations/logo-parakou.png')
        ->and($i)->toContain('/img/badges/icon-location.png');

    // La maquette officielle sert de BASE à améliorer (le cadre y figure).
    expect($i)->toContain('BASE À AMÉLIORER')
        ->and($i)->toContain('bg_image');
});

it('adapte la consigne au type attestation (cadre et jetons propres)', function () {
    $sink = new stdClass;
    $sink->instruction = '';
    bindFakeAi(capturingAiProvider($sink));

    app(GenerateDocumentLayout::class)->handle('attestation', 'communication', 'Style sobre');

    $i = $sink->instruction;

    expect($i)->toContain('/img/attestations/attestation-communication-bg.png')
        ->and($i)->toContain('[signatory.name]')
        ->and($i)->toContain('[certificate.issued_on]')
        // Pas de jetons spécifiques au badge ici.
        ->and($i)->not->toContain('/img/badges/icon-location.png');
});

it('la génération assainie reste une mise en page de badge valide', function () {
    $sink = new stdClass;
    $sink->instruction = '';
    bindFakeAi(capturingAiProvider($sink));

    $layout = app(GenerateDocumentLayout::class)->handle('badge', 'default', 'Peu importe');

    // Sortie vide -> retombe sur la maquette officielle du badge (A6 portrait, cadre).
    expect($layout['width'])->toBe(333)
        ->and($layout['height'])->toBe(472)
        ->and($layout['bg_image'])->toBe('/img/badges/badge-default-bg.png')
        ->and($layout['elements'])->not->toBeEmpty();
});
