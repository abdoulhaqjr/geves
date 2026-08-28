<?php

use App\Actions\IssueAttestation;
use App\Models\Account;

// openEdition(), verifiedAccount() -> AccountSubmissionTest

function participantWithActivity(): array
{
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-007', 'title' => 'Innovation locale', 'status' => 'accepted',
    ]);
    $payment = $edition->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-2026-0007', 'paid_at' => now(),
    ]);
    $attestation = (new IssueAttestation)->handle($participant, 'participation');

    return compact('edition', 'account', 'participant', 'submission', 'payment', 'attestation');
}

it('affiche les blocs personnels sur le tableau de bord', function () {
    ['account' => $account] = participantWithActivity();

    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('26-007')
        ->assertSee('Innovation locale')
        ->assertSee('Reçu')
        ->assertSee('Participation')       // attestation type
        ->assertSee('Télécharger');
});

it('donne au participant l’accès à son reçu', function () {
    $data = participantWithActivity();

    $this->actingAs($data['account'], 'account')
        ->get(route('account.receipt', $data['payment']))
        ->assertOk()
        ->assertSee('REC-2026-0007');
});

it('interdit le reçu d’un autre participant', function () {
    $data = participantWithActivity();
    $other = Account::create(['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.tg', 'password' => 'pass12345']);

    $this->actingAs($other, 'account')
        ->get(route('account.receipt', $data['payment']))
        ->assertNotFound();
});

it('refuse le reçu d’un paiement non réglé', function () {
    $edition = openEdition();
    $account = verifiedAccount();
    $participant = $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'K', 'email' => $account->email]);
    $pending = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'status' => 'pending']);

    $this->actingAs($account, 'account')->get(route('account.receipt', $pending))->assertNotFound();
});
