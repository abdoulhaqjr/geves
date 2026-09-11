<?php

use App\Actions\IssueAttestation;
use App\Models\Account;
use App\Models\DocumentTemplate;
use App\Models\User;

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
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);

    // Une communication acceptée -> livret (HTML + Word) disponible.
    $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'Innovation durable',
        'abstract' => 'Résumé.', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted',
        'authors' => [['name' => 'Awa Kodjo', 'corresponding' => true]],
    ]);
    // Un paiement réglé -> reçu.
    $edition->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-2026-1',
    ]);
    // Une attestation.
    (new IssueAttestation)->handle($participant, 'participation');

    $this->actingAs($account, 'account')
        ->get(route('account.documents'))
        ->assertOk()
        ->assertSee('Mon badge participant')
        ->assertSee('Mes documents')
        ->assertSee('Attestation de participation')
        ->assertSee('Livret (Word)')
        ->assertSee('Livret (imprimable)')
        ->assertSee('Reçu');
});

it('rend l’aperçu du badge à partir du modèle admin, avec les vraies données', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'role' => 'author',
    ]);

    // Modèle de badge conçu dans l'admin (schéma [namespace.key] de l'éditeur).
    DocumentTemplate::create([
        'edition_id' => $edition->id, 'kind' => 'badge', 'type' => 'default',
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
