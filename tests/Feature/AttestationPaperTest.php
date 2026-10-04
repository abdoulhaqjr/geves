<?php

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Support\AttestationPolicy;
use App\Support\EditionSettings;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest

/**
 * **Sortie papier des attestations.**
 *
 * ⚠️ Sans elle, la délivrance physique revenait à ouvrir les attestations une
 * par une à leur adresse de vérification : deux cents communications, deux cents
 * onglets. Le mode papier existait sans le geste qui le rend tenable.
 *
 * @return array{0: Edition, 1: Attestation, 2: Attestation}
 */
function editionPapier(): array
{
    $edition = Edition::factory()->create();
    EditionSettings::set($edition, 'attestations.delivery', AttestationPolicy::DELIVERY_PAPER);

    $faire = function (string $nom, string $type) use ($edition) {
        $participant = Participant::factory()->for($edition)->create(['first_name' => $nom]);

        return Attestation::create([
            'uuid' => (string) Str::uuid(),
            'edition_id' => $edition->id,
            'participant_id' => $participant->id,
            'type' => $type,
            'recipient_name' => $nom.' Test',
            'recipient_email' => mb_strtolower($nom).'@example.tg',
            'revoked' => false,
            'issued_at' => now(),
        ]);
    };

    return [$edition, $faire('Awa', 'participation'), $faire('Kofi', 'participation')];
}

it('sort un lot PDF imprimable pour le filtre affiché', function () {
    [$edition] = editionPapier();

    $reponse = $this->actingAs(adminStaff(), 'web')->get(route('admin.documents.attestations.lot', [
        'edition_id' => $edition->id, 'type' => 'participation',
    ]));

    $reponse->assertOk();
    $reponse->assertHeader('content-type', 'application/pdf');
    expect(substr($reponse->getContent(), 0, 5))->toBe('%PDF-');
});

/**
 * ⚠️ Un lot n'a qu'UNE taille de page : elle vient de la mise en page du modèle,
 * et mêler une A4 paysage à une A5 portrait sortirait des documents rognés. Le
 * dire plutôt que de produire ce lot-là.
 */
it('refuse un lot sans type, qui mêlerait plusieurs tailles de page', function () {
    [$edition] = editionPapier();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.attestations.lot', ['edition_id' => $edition->id]))
        ->assertRedirect();

    // Et un filtre qui ne ramène personne le dit, au lieu de rendre un PDF vide.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.attestations.lot', [
            'edition_id' => $edition->id, 'type' => 'moderation',
        ]))
        ->assertRedirect();
});

it('sort la liste d’émargement avec ses bénéficiaires', function () {
    [$edition] = editionPapier();

    $reponse = $this->actingAs(adminStaff(), 'web')->get(route('admin.documents.attestations.emargement', [
        'edition_id' => $edition->id, 'type' => 'participation',
    ]));

    $reponse->assertOk();
    $reponse->assertHeader('content-type', 'application/pdf');
});

/**
 * ⚠️ **Émettre n'est pas remettre.** Une attestation peut être émise, imprimée,
 * et attendre des mois dans une chemise parce que personne ne sait ce qui reste
 * à distribuer.
 *
 * ⚠️ Réversible, contrairement au passage d'une communication : se tromper de
 * ligne au guichet est banal, et une remise fausse ferait croire que quelqu'un a
 * déjà son document.
 */
it('marque une attestation remise, et sait revenir en arrière', function () {
    [, $awa] = editionPapier();
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->post(route('admin.documents.attestations.deliver', $awa))
        ->assertRedirect();

    $awa->refresh();
    expect($awa->isDelivered())->toBeTrue();
    expect($awa->delivered_by)->toBe($staff->id);

    $this->actingAs($staff, 'web')->post(route('admin.documents.attestations.deliver', $awa));

    $awa->refresh();
    expect($awa->isDelivered())->toBeFalse();
    expect($awa->delivered_by)->toBeNull();
});

/** « Ce qui reste à remettre » est la question du guichet : elle a son filtre. */
it('filtre la liste sur ce qui reste à remettre', function () {
    [$edition, $awa, $kofi] = editionPapier();
    $awa->forceFill(['delivered_at' => now()])->save();

    $reponse = $this->actingAs(adminStaff(), 'web')->get(route('admin.documents.attestations', [
        'edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'delivrees', 'delivery' => 'pending',
    ]));

    $reponse->assertOk();
    $reponse->assertSee($kofi->recipient_name);
    $reponse->assertDontSee($awa->recipient_name);
});

/**
 * ⚠️ Le bouton « Émettre » engageait un lot sans jamais dire ce qu'il contenait.
 * La page qui émet doit annoncer le mode de délivrance : en ligne, chaque
 * attestation part chez son bénéficiaire ; sur papier, rien ne part.
 */
it('annonce le mode de délivrance sur la page qui émet', function () {
    [$edition] = editionPapier();

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.attestations', [
            'edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'modeles',
        ]))
        ->assertOk()
        ->assertSee(AttestationPolicy::DELIVERIES[AttestationPolicy::DELIVERY_PAPER])
        ->assertSee('Rien n’est envoyé', false);
});

/**
 * ⚠️ Le point d'entrée du papier : les lots s'affichent SUR la liste, avec les
 * filtres de l'écran - on imprime ce qu'on regarde. Sans type choisi, on le dit
 * au lieu de produire un lot qui mêlerait plusieurs tailles de page.
 */
it('pose les lots à imprimer sur la liste délivrée', function () {
    [$edition] = editionPapier();
    $base = ['edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'delivrees'];

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.attestations', $base + ['type' => 'participation']))
        ->assertOk()
        ->assertSee('Lot PDF à imprimer')
        ->assertSee('Liste d’émargement', false);

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.documents.attestations', $base))
        ->assertOk()
        ->assertSee('Choisissez un type pour exporter le lot');
});
