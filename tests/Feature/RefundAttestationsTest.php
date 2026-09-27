<?php

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\Payment;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php

/** Participant + paiement réglé d'une nature donnée. */
function paidPayment($edition, $participant, array $meta = [], float $amount = 10000): Payment
{
    return Payment::create([
        'edition_id' => $edition->id, 'participant_id' => $participant->id,
        'amount' => $amount, 'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'paid',
        'meta' => $meta ?: null,
    ]);
}

function refundParticipant($edition): array
{
    $user = verifiedAccount();
    $participant = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);

    return [$user, $participant];
}

it('révoque participation et communications au remboursement des frais d’inscription', function () {
    $edition = openEdition();
    [$user, $participant] = refundParticipant($edition);
    $submission = $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-500', 'title' => 'Comm',
        'abstract' => 'x', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'authors' => [],
    ]);

    $payment = paidPayment($edition, $participant);
    app(IssueAttestation::class)->handle($participant, 'participation');
    app(IssueAttestation::class)->handle($participant, 'communication', $submission);

    expect(Attestation::where('revoked', false)->count())->toBe(2);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payment))->assertRedirect();

    expect(Attestation::where('type', 'participation')->first()->revoked)->toBeTrue();
    expect(Attestation::where('type', 'communication')->first()->revoked)->toBeTrue();
});

it('conserve les attestations s’il reste un paiement de participation réglé', function () {
    $edition = openEdition();
    [$user, $participant] = refundParticipant($edition);

    $first = paidPayment($edition, $participant);
    paidPayment($edition, $participant); // second règlement (ex. encaissement manuel)
    app(IssueAttestation::class)->handle($participant, 'participation');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $first))->assertRedirect();

    expect(Attestation::where('type', 'participation')->first()->revoked)->toBeFalse();
});

it('révoque l’attestation de formation au remboursement de cette formation', function () {
    $edition = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($edition);
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier', 'fee' => 10000, 'published' => true]);

    $payment = paidPayment($edition, $participant, ['flow' => 'formation', 'formation_id' => $formation->id]);
    app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payment))->assertRedirect();

    expect(Attestation::where('type', 'formation')->first()->revoked)->toBeTrue();
});

it('ne touche pas à l’attestation d’une AUTRE formation encore réglée', function () {
    $edition = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($edition);
    $a = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier A', 'fee' => 10000, 'published' => true]);
    $b = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier B', 'fee' => 8000, 'published' => true]);

    $payA = paidPayment($edition, $participant, ['flow' => 'formation', 'formation_id' => $a->id]);
    paidPayment($edition, $participant, ['flow' => 'formation', 'formation_id' => $b->id], 8000);
    app(IssueAttestation::class)->handle($participant, 'formation', null, $a);
    app(IssueAttestation::class)->handle($participant, 'formation', null, $b);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payA))->assertRedirect();

    expect(Attestation::where('formation_id', $a->id)->first()->revoked)->toBeTrue();
    expect(Attestation::where('formation_id', $b->id)->first()->revoked)->toBeFalse();
});

it('un remboursement de formation ne touche pas la participation, et inversement', function () {
    $edition = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($edition);
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier', 'fee' => 10000, 'published' => true]);

    $formationPay = paidPayment($edition, $participant, ['flow' => 'formation', 'formation_id' => $formation->id]);
    paidPayment($edition, $participant, [], 25000); // frais de participation, toujours réglés
    app(IssueAttestation::class)->handle($participant, 'participation');
    app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $formationPay))->assertRedirect();

    expect(Attestation::where('type', 'formation')->first()->revoked)->toBeTrue();
    expect(Attestation::where('type', 'participation')->first()->revoked)->toBeFalse();
});

it('ne révoque pas l’attestation d’animation d’un formateur (il ne paie pas)', function () {
    $edition = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($edition);
    $formation = Formation::create(['edition_id' => $edition->id, 'title' => 'Atelier', 'fee' => 10000, 'published' => true]);

    $payment = paidPayment($edition, $participant, ['flow' => 'formation', 'formation_id' => $formation->id]);
    app(IssueAttestation::class)->handle($participant, 'formation_animation', null, $formation);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payment))->assertRedirect();

    expect(Attestation::where('type', 'formation_animation')->first()->revoked)->toBeFalse();
});
