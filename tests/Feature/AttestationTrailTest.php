<?php

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\AttestationEvent;
use App\Support\AttestationTrail;

/**
 * **Vie d'une attestation après sa délivrance** : a-t-elle été ouverte, reçue
 * par son titulaire, partagée, portée vers LinkedIn ?
 *
 * ⚠️ **Délivrer n'est pas être reçu.** Une attestation en ligne émise, notifiée
 * et jamais ouverte était indiscernable d'une attestation lue et partagée - et
 * c'est pourtant la seule chose que le gestionnaire veut savoir avant de
 * relancer. Ces cas gardent surtout ce que les compteurs NE doivent PAS dire.
 */
function attestationPourSuivi(array $surParticipant = []): Attestation
{
    $edition = openEdition();
    $participant = $edition->participants()->create(array_merge([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ], $surParticipant));

    return (new IssueAttestation)->handle($participant, 'participation');
}

it('constate l’ouverture et ne compte pas deux fois le même visiteur', function () {
    $att = attestationPourSuivi();

    $this->get(route('attestation.document', $att->uuid))->assertOk();
    // ⚠️ Un F5 ne vaut pas une seconde ouverture : sans fenêtre de
    // dédoublonnage, le gestionnaire lirait « 12 ouvertures » là où une seule
    // personne est passée.
    $this->get(route('attestation.document', $att->uuid))->assertOk();

    $att->refresh();
    expect($att->views_count)->toBe(1)
        ->and($att->first_viewed_at)->not->toBeNull()
        ->and($att->last_viewed_at)->not->toBeNull()
        ->and($att->wasOpened())->toBeTrue()
        // Une ouverture ANONYME n'est pas un accusé de réception : le lien
        // circule, et un recruteur qui le suit n'est pas le bénéficiaire.
        ->and($att->acknowledged_at)->toBeNull()
        ->and($att->isAcknowledged())->toBeFalse();

    expect(AttestationEvent::where('attestation_id', $att->id)->count())->toBe(1);
    expect(AttestationEvent::where('attestation_id', $att->id)->first()->actor)
        ->toBe(AttestationEvent::ACTOR_PUBLIC);
});

it('ne compte pas les consultations du back-office', function () {
    $att = attestationPourSuivi();

    // ⚠️ La liste des attestations délivrées ouvre le document dans un onglet :
    // si le clic du gestionnaire comptait, toute attestation qu'il relit
    // passerait pour « ouverte », et la colonne dirait l'activité du secrétariat
    // au lieu de celle du bénéficiaire.
    $this->actingAs(adminStaff())->get(route('attestation.document', $att->uuid))->assertOk();

    $att->refresh();
    expect($att->views_count)->toBe(0)
        ->and($att->first_viewed_at)->toBeNull()
        ->and($att->acknowledged_at)->toBeNull();

    // Le geste reste au journal : c'est lui qui prouve que le compteur est propre.
    $evenement = AttestationEvent::where('attestation_id', $att->id)->first();
    expect($evenement)->not->toBeNull()
        ->and($evenement->actor)->toBe(AttestationEvent::ACTOR_STAFF);
});

it('pose l’accusé de réception quand le titulaire ouvre son attestation', function () {
    $compte = verifiedAccount();
    $att = attestationPourSuivi(['user_id' => $compte->id, 'email' => $compte->email]);

    $this->actingAs($compte, 'account')->get(route('attestation.document', $att->uuid))->assertOk();

    $att->refresh();
    expect($att->isAcknowledged())->toBeTrue()
        ->and($att->views_count)->toBe(1);

    expect(AttestationEvent::where('attestation_id', $att->id)->first()->actor)
        ->toBe(AttestationEvent::ACTOR_HOLDER);
});

it('n’accuse pas réception pour le compte d’un AUTRE titulaire', function () {
    $att = attestationPourSuivi();
    // Un compte connecté qui ouvre l'attestation de quelqu'un d'autre reste un
    // visiteur : sinon le premier collègue connecté signerait la réception.
    $autre = verifiedAccount();

    $this->actingAs($autre, 'account')->get(route('attestation.document', $att->uuid))->assertOk();

    $att->refresh();
    expect($att->acknowledged_at)->toBeNull()
        ->and($att->views_count)->toBe(1);
});

it('compte le téléchargement du PDF comme une ouverture, sans confondre les gestes', function () {
    $att = attestationPourSuivi();

    $this->get(route('attestation.pdf', $att->uuid))->assertOk();

    $att->refresh();
    expect($att->views_count)->toBe(1)
        ->and($att->first_viewed_at)->not->toBeNull();

    // Le journal distingue les deux gestes, la synthèse les additionne.
    expect(AttestationEvent::where('attestation_id', $att->id)->first()->kind)->toBe('download');
});

it('constate les gestes que seule la page peut déclarer', function () {
    $att = attestationPourSuivi();

    $this->postJson(route('attestation.trace', $att->uuid), ['kind' => 'share'])->assertOk();
    $this->postJson(route('attestation.trace', $att->uuid), ['kind' => 'linkedin'])->assertOk();

    $att->refresh();
    expect($att->shares_count)->toBe(1)
        ->and($att->shared_at)->not->toBeNull()
        ->and($att->linkedin_at)->not->toBeNull()
        // Partager n'est pas ouvrir : le geste vient de la page, qui était déjà
        // ouverte, et ne doit pas gonfler le compteur d'ouvertures.
        ->and($att->views_count)->toBe(0);
});

it('refuse de se faire déclarer une ouverture, et tout geste inconnu', function () {
    $att = attestationPourSuivi();

    // ⚠️ L'ouverture et le téléchargement sont constatés PAR LE SERVEUR : les
    // laisser se déclarer permettrait de s'inventer des lectures.
    $this->postJson(route('attestation.trace', $att->uuid), ['kind' => 'view'])->assertStatus(422);
    $this->postJson(route('attestation.trace', $att->uuid), ['kind' => 'bidon'])->assertStatus(422);

    expect(AttestationEvent::where('attestation_id', $att->id)->count())->toBe(0);
    expect($att->refresh()->views_count)->toBe(0);
});

it('recalcule la synthèse depuis le journal', function () {
    $compte = verifiedAccount();
    $att = attestationPourSuivi(['user_id' => $compte->id, 'email' => $compte->email]);

    $this->actingAs($compte, 'account')->get(route('attestation.document', $att->uuid))->assertOk();
    $this->postJson(route('attestation.trace', $att->uuid), ['kind' => 'copy'])->assertOk();

    // ⚠️ C'est ce qui rend la dénormalisation tenable : le journal reste la
    // source de vérité, et les colonnes ne sont qu'une vue qu'on sait refaire.
    $att->forceFill([
        'acknowledged_at' => null, 'first_viewed_at' => null, 'last_viewed_at' => null,
        'views_count' => 0, 'shared_at' => null, 'shares_count' => 0, 'linkedin_at' => null,
    ])->save();

    AttestationTrail::recompute($att->refresh());

    $att->refresh();
    expect($att->views_count)->toBe(1)
        ->and($att->shares_count)->toBe(1)
        ->and($att->isAcknowledged())->toBeTrue();
});

it('dit ce qui a été constaté, et pas davantage', function () {
    $att = attestationPourSuivi();

    // Jamais ouverte : c'est l'état par défaut, et il se lit.
    expect(collect(AttestationTrail::pastilles($att))->pluck('label'))->toContain('Jamais ouverte');

    $att->forceFill([
        'first_viewed_at' => now(), 'last_viewed_at' => now(), 'views_count' => 3,
        'linkedin_at' => now(),
    ])->save();

    $pastilles = collect(AttestationTrail::pastilles($att));
    expect($pastilles->pluck('label'))->toContain('Ouverte ×3')->toContain('LinkedIn');

    // ⚠️ LinkedIn ne nous rappelle jamais : rien ne doit affirmer que
    // l'attestation FIGURE au profil.
    $linkedin = $pastilles->firstWhere('label', 'LinkedIn');
    expect($linkedin['title'])->toContain('Ajouter au profil')
        ->and($linkedin['title'])->toContain('ne nous dit pas');
});

it('laisse le gestionnaire isoler les attestations jamais ouvertes', function () {
    $ouverte = attestationPourSuivi();
    $edition = $ouverte->edition;
    $dormante = (new IssueAttestation)->handle(
        $edition->participants()->create([
            'first_name' => 'Kossi', 'last_name' => 'Mensah', 'email' => 'kossi@example.tg', 'role' => 'author',
        ]),
        'participation',
    );

    $this->get(route('attestation.document', $ouverte->uuid))->assertOk();

    $base = ['edition_id' => $edition->id, 'tab' => 'attestations', 'stab' => 'delivrees'];

    $this->actingAs(adminStaff())
        ->get(route('admin.documents.attestations', $base + ['trail' => 'unopened']))
        ->assertOk()
        ->assertSee('Kossi Mensah')
        ->assertDontSee('Awa Kodjo');

    $this->actingAs(adminStaff())
        ->get(route('admin.documents.attestations', $base + ['trail' => 'opened']))
        ->assertOk()
        ->assertSee('Awa Kodjo')
        ->assertDontSee('Kossi Mensah');

    expect($dormante->refresh()->wasOpened())->toBeFalse();
});

it('ne compte pas le robot d’aperçu qui suit le lien partagé', function () {
    $att = attestationPourSuivi();

    // ⚠️ Le titulaire colle le lien dans une conversation : le robot d'aperçu va
    // chercher le titre et l'image AVANT que le destinataire ait vu le message.
    // Compter cette lecture ferait dire « ouverte » d'une attestation que
    // personne n'a lue - pire que de ne rien afficher.
    foreach (['WhatsApp/2.23 A', 'facebookexternalhit/1.1', 'LinkedInBot/1.0', 'Slackbot-LinkExpanding 1.0'] as $agent) {
        $this->withHeader('User-Agent', $agent)
            ->get(route('attestation.document', $att->uuid))->assertOk();
    }

    $att->refresh();
    expect($att->views_count)->toBe(0)
        ->and($att->wasOpened())->toBeFalse();
    expect(AttestationEvent::where('attestation_id', $att->id)->count())->toBe(0);

    // Une personne, derrière, compte normalement.
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone) Safari/604.1')
        ->get(route('attestation.document', $att->uuid))->assertOk();

    expect($att->refresh()->views_count)->toBe(1);
});
