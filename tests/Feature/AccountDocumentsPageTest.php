<?php

use App\Actions\IssueAttestation;
use App\Livewire\Account\Documents;
use App\Models\Account;
use Livewire\Livewire;

// openEdition() / verifiedAccount() -> AccountSubmissionTest

it('protège la page Mes documents derrière le guard account', function () {
    $this->get(route('account.documents'))->assertRedirect(route('account.login'));
});

it('affiche l’état vide quand le compte n’a aucun document', function () {
    $account = Account::create([
        'first_name' => 'Sans', 'last_name' => 'Document',
        'email' => 'vide@example.tg', 'password' => 'pass12345',
    ]);

    Livewire::actingAs($account, 'account')
        ->test(Documents::class)
        ->assertSee('Aucun document');
});

it('liste attestations, reçus et livret sur la page Mes documents', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
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

    Livewire::actingAs($account, 'account')
        ->test(Documents::class)
        ->assertSee('Mes attestations')
        ->assertSee('Livret (Word)')
        ->assertSee('Livret (imprimable)')
        ->assertSee('Reçu');
});
