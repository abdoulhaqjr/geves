<?php

use App\Actions\IssueAttestation;
use App\Models\User;

// openEdition(), verifiedAccount() -> AccountSubmissionTest

function participantWithActivity(): array
{
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email,
    ]);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-007', 'title' => 'Innovation locale', 'status' => 'accepted',
    ]);
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-2026-0007', 'paid_at' => now(),
    ]);
    $attestation = (new IssueAttestation)->handle($participant, 'participation');

    return compact('event', 'account', 'participant', 'submission', 'payment', 'attestation');
}

it('affiche les blocs personnels sur le tableau de bord', function () {
    ['account' => $account, 'payment' => $payment, 'attestation' => $attestation] = participantWithActivity();

    // ⚠️ Le cas vérifiait la présence du mot « Télécharger », qui était le libellé
    // des boutons des anciennes cartes « Paiements & reçus » / « Mes attestations ».
    // Les trois cartes de fin de page ont été réunies en UN rang de pastilles, où
    // chaque pastille porte le NOM du document et son format. On éprouve donc ce
    // que le cas voulait vraiment dire - « ses pièces sont à portée de clic » -
    // par les ADRESSES, qui ne dépendent d'aucun libellé.
    $this->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('26-007')
        ->assertSee('Innovation locale')
        ->assertSee('Participation')       // type d'attestation
        ->assertSee(route('account.receipt', $payment))
        ->assertSee(route('attestation.document', $attestation->uuid));
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
    $other = User::create(['first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.tg', 'password' => 'pass12345']);

    $this->actingAs($other, 'account')
        ->get(route('account.receipt', $data['payment']))
        ->assertNotFound();
});

it('refuse le reçu d’un paiement non réglé', function () {
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'K', 'email' => $account->email]);
    $pending = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'status' => 'pending']);

    $this->actingAs($account, 'account')->get(route('account.receipt', $pending))->assertNotFound();
});
