<?php

use App\Actions\IssueAttestation;
use App\Documents\AttestationVars;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Support\EditionSettings;

/**
 * Page publique d'une attestation : c'est l'adresse que porte le QR code et que
 * le bénéficiaire partage. Elle doit tenir debout devant un tiers - le document,
 * qui l'a délivrée, ce qu'elle certifie et comment la vérifier.
 */
it('présente l’attestation, son émetteur et ce qu’elle certifie', function () {
    $edition = openEdition();
    $edition->colloque->update(['description' => 'Rendez-vous annuel des universités partenaires.']);
    $edition->update(['theme' => 'Innover pour un avenir durable']);

    $p = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertSee($edition->colloque->name)
        ->assertSee('Rendez-vous annuel des universités partenaires.')
        ->assertSee('Innover pour un avenir durable')
        ->assertSee($att->uuid)
        // Les gestes attendus d'une page d'attestation.
        ->assertSee('Partager l’attestation', false)
        ->assertSee(route('attestation.pdf', $att->uuid))
        ->assertSee('linkedin.com/profile/add', false)
        // ⚠️ La vérification se fait SUR CETTE PAGE : le bouton interroge
        // l'endpoint JSON, il n'emmène plus le visiteur ailleurs.
        ->assertSee(route('attestation.verify.json', $att->uuid))
        ->assertDontSee('href="'.route('attestation.verify', $att->uuid).'"', false);
});

it('vérifie l’attestation sur place, sans quitter la page', function () {
    $edition = openEdition();
    $p = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->getJson(route('attestation.verify.json', $att->uuid))
        ->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('facts.Bénéficiaire', 'Awa Kodjo');

    $att->update(['revoked' => true]);
    $this->getJson(route('attestation.verify.json', $att->uuid))
        ->assertOk()
        ->assertJsonPath('status', 'revoked');

    $this->getJson(route('attestation.verify.json', 'code-bidon'))
        ->assertNotFound()
        ->assertJsonPath('found', false);
});

it('dit qu’une attestation révoquée ne vaut plus preuve', function () {
    $edition = openEdition();
    $p = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');
    $att->update(['revoked' => true]);

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('révoquée')
        ->assertSee('ne vaut plus comme preuve');
});

it('n’affiche pas d’intitulé sans valeur', function () {
    $edition = openEdition();
    // Ni thème ni lieu : les lignes correspondantes ne doivent pas apparaître
    // vides - un intitulé sans valeur donne l'impression d'un gabarit cassé.
    $edition->update(['theme' => null, 'location' => null]);

    $p = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertDontSee('<dt>Thème</dt>', false)
        ->assertDontSee('<dt>Lieu</dt>', false);
});

it('remplace [signatory.name] par le signataire réglé pour l’édition', function () {
    $edition = openEdition();
    EditionSettings::setMany($edition, [
        'attestations.signatory' => 'Pr. Padabo KADOUZA',
        'attestations.signatory_role' => 'Président du comité d’organisation',
    ]);

    $p = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    // Le modèle livré porte le jeton ; sans valeur réglée, la ligne sortait vide.
    $tpl = DocumentTemplate::ensureFor((int) $edition->id, 'attestation', 'participation');
    $layout = (array) $tpl->layout;
    $layout['elements'][] = ['type' => 'text', 'x' => 10, 'y' => 10, 'w' => 400, 'h' => 30, 'text' => '[signatory.name]'];
    $tpl->forceFill(['layout' => $layout])->save();

    $this->get(route('attestation.document', $att->uuid))
        ->assertOk()
        ->assertSee('Pr. Padabo KADOUZA');

    expect(Attestation::whereKey($att->id)->value('revoked'))->toBeFalsy();
});

it('fait pointer le QR du document vers la page de l’attestation', function () {
    // ⚠️ Le QR menait à la page de vérification seule : on scannait une
    // attestation pour atterrir sur un verdict, sans le document.
    $edition = openEdition();
    $p = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $att = (new IssueAttestation)->handle($p, 'participation');

    $vars = AttestationVars::for($att->fresh(), route('attestation.document', $att->uuid));

    expect($vars['verify_url'])->toBe(route('attestation.document', $att->uuid));
});
