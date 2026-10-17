<?php

use App\Actions\IssueAttestation;
use App\Models\DocumentTemplate;
use App\Models\Formation;
use App\Models\User;
use App\Support\AttestationPolicy;
use App\Support\EventSettings;

// openEdition() / verifiedAccount() -> AccountSubmissionTest

it('protège la page Mes documents derrière le guard account', function () {
    $this->get(route('account.documents'))->assertRedirect(route('account.login'));
});

it('affiche l’état vide quand le compte n’a aucun document', function () {
    $account = User::create([
        'first_name' => 'Sans', 'last_name' => 'Document',
        'email' => 'vide@example.tg', 'password' => 'pass12345',
    ]);

    $this->actingAs($account, 'account')
        ->get(route('account.documents'))
        ->assertOk()
        ->assertSee('Aucun document');
});

it('liste attestations, reçus et livret sur la page Mes documents', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    // Une communication acceptée -> livret (HTML + Word) disponible.
    $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Innovation durable',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'authors' => [['name' => 'Awa Kodjo', 'corresponding' => true]],
    ]);
    // Un paiement réglé -> reçu.
    $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-2026-1',
    ]);
    // Une attestation.
    (new IssueAttestation)->handle($participant, 'participation');

    $this->actingAs($account, 'account')
        ->get(route('account.documents'))
        ->assertOk()
        // ⚠️ « Mon badge » / « Mes badges » : l'intitulé s'accorde depuis qu'une
        // personne peut cumuler plusieurs badges de fonction.
        ->assertSee('Mon badge')
        ->assertSee('Mes documents')
        ->assertSee('Attestation de participation')
        ->assertSee('Livret (Word)')
        ->assertSee('Livret (imprimable)')
        ->assertSee('Reçu');
});

it('rend l’aperçu du badge à partir du modèle admin, avec les vraies données', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'author',
    ]);

    // Modèle de badge conçu dans l'admin (schéma [namespace.key] de l'éditeur).
    DocumentTemplate::create([
        'event_id' => $event->id, 'kind' => 'badge', 'type' => 'default',
        'layout' => [
            'width' => 320, 'height' => 440, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'text', 'x' => 20, 'y' => 70, 'w' => 280, 'h' => 22, 'text' => '[edition.name]', 'size' => 12, 'weight' => 700, 'color' => '#223ea4', 'align' => 'center'],
                ['type' => 'text', 'x' => 20, 'y' => 210, 'w' => 280, 'h' => 40, 'text' => '[recipient.name]', 'size' => 22, 'weight' => 800, 'color' => '#1a2332', 'align' => 'center'],
                ['type' => 'text', 'x' => 20, 'y' => 252, 'w' => 280, 'h' => 24, 'text' => '[RECIPIENT.ROLE]', 'size' => 13, 'weight' => 600, 'color' => '#5a6577', 'align' => 'center'],
                ['type' => 'text', 'x' => 20, 'y' => 392, 'w' => 280, 'h' => 20, 'text' => '[badge.ref]', 'size' => 11, 'weight' => 700, 'color' => '#8b95a8', 'align' => 'center'],
            ],
        ],
    ]);

    $html = $this->actingAs($account, 'account')
        ->get(route('account.documents'))
        ->assertOk()
        ->assertSee('Awa Kodjo')          // [recipient.name] substitué
        ->assertSee('ukc-doc-canvas', false) // le canvas du modèle est rendu
        ->getContent();

    // Aucun jeton brut ne doit subsister dans l'aperçu.
    expect($html)->not->toContain('[recipient.name]')
        ->not->toContain('[edition.name]')
        ->not->toContain('[badge.ref]');
});

/**
 * ⚠️ La page n'affichait **qu'une** attestation de formation, sans dire laquelle :
 * quelqu'un qui a suivi trois ateliers n'en voyait qu'un, et pouvait croire les
 * autres perdus. L'animation y figure aussi - le formateur est, lui aussi,
 * attestataire de cet événement.
 */
it('liste chaque attestation de formation et d’animation, avec son intitulé', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    $suivie = Formation::create([
        'event_id' => $event->id, 'title' => 'Atelier statistiques appliquées', 'slug' => 'stats-'.$event->id,
    ]);
    $animee = Formation::create([
        'event_id' => $event->id, 'title' => 'Atelier rédaction scientifique', 'slug' => 'redac-'.$event->id,
    ]);

    (new IssueAttestation)->handle($participant, 'formation', null, $suivie);
    (new IssueAttestation)->handle($participant, 'formation_animation', null, $animee);

    $this->actingAs($account, 'account')
        ->get(route('account.documents'))
        ->assertOk()
        ->assertSee('Formations')
        ->assertSee('Attestation de formation')
        ->assertSee('Attestation d’animation de formation', false)
        ->assertSee('Atelier statistiques appliquées')
        ->assertSee('Atelier rédaction scientifique');
});

/**
 * ⚠️ Les attestations de communication sont **une par communication**. Indexées
 * par type, celle de la dernière s'affichait en face de toutes les autres : deux
 * communications renvoyaient au même document.
 */
it('rattache chaque attestation de communication à SA communication', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    $faire = fn (string $ref, string $titre) => $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => $ref, 'title' => $titre,
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'authors' => [['name' => 'Awa Kodjo', 'corresponding' => true]],
    ]);

    $a1 = (new IssueAttestation)->handle($participant, 'communication', $faire('26-101', 'Première communication'));
    $a2 = (new IssueAttestation)->handle($participant, 'communication', $faire('26-102', 'Seconde communication'));

    expect($a1->id)->not->toBe($a2->id);

    // Chaque communication porte SON identifiant d'attestation.
    $this->actingAs($account, 'account')->get(route('account.documents'))
        ->assertOk()
        ->assertSee($a1->uuid)
        ->assertSee($a2->uuid);
});

/**
 * ⚠️ Délivrance PHYSIQUE : l'attestation se voit et se vérifie, mais ne se
 * télécharge pas - l'exemplaire qui fait foi est remis en main propre. Laisser le
 * téléchargement viderait le choix de son sens.
 */
it('montre l’attestation sans la laisser télécharger en délivrance papier', function () {
    $event = openEdition();
    EventSettings::set($event, 'attestations.delivery', AttestationPolicy::DELIVERY_PAPER);
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    (new IssueAttestation)->handle($participant, 'participation');

    $this->actingAs($account, 'account')
        ->get(route('account.documents'))
        ->assertOk()
        ->assertSee('Attestation de participation')
        ->assertSee('Vérifier (spécimen)')
        ->assertSee('À retirer au secrétariat')
        ->assertDontSee('>Télécharger<', false);
});
