<?php

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\Payment;

// openEdition()/verifiedAccount()/adminStaff() -> tests/Pest.php

/** Participant + paiement réglé d'une nature donnée. */
function paidPayment($event, $participant, array $meta = [], float $amount = 10000): Payment
{
    return Payment::create([
        'event_id' => $event->id, 'participant_id' => $participant->id,
        'amount' => $amount, 'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'paid',
        'meta' => $meta ?: null,
    ]);
}

function refundParticipant($event): array
{
    $user = verifiedAccount();
    $participant = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);

    return [$user, $participant];
}

it('révoque participation et communications au remboursement des frais d’inscription', function () {
    $event = openEdition();
    [$user, $participant] = refundParticipant($event);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-500', 'title' => 'Comm',
        'abstract' => 'x', 'track' => 'Sciences', 'type' => 'oral', 'status' => 'accepted', 'authors' => [],
    ]);

    $payment = paidPayment($event, $participant);
    app(IssueAttestation::class)->handle($participant, 'participation');
    app(IssueAttestation::class)->handle($participant, 'communication', $submission);

    expect(Attestation::where('revoked', false)->count())->toBe(2);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payment))->assertRedirect();

    expect(Attestation::where('type', 'participation')->first()->revoked)->toBeTrue();
    expect(Attestation::where('type', 'communication')->first()->revoked)->toBeTrue();
});

it('conserve les attestations s’il reste un paiement de participation réglé', function () {
    $event = openEdition();
    [$user, $participant] = refundParticipant($event);

    $first = paidPayment($event, $participant);
    paidPayment($event, $participant); // second règlement (ex. encaissement manuel)
    app(IssueAttestation::class)->handle($participant, 'participation');

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $first))->assertRedirect();

    expect(Attestation::where('type', 'participation')->first()->revoked)->toBeFalse();
});

it('révoque l’attestation de formation au remboursement de cette formation', function () {
    $event = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($event);
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Atelier', 'fee' => 10000, 'published' => true]);

    $payment = paidPayment($event, $participant, ['flow' => 'formation', 'formation_id' => $formation->id]);
    app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payment))->assertRedirect();

    expect(Attestation::where('type', 'formation')->first()->revoked)->toBeTrue();
});

it('ne touche pas à l’attestation d’une AUTRE formation encore réglée', function () {
    $event = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($event);
    $a = Formation::create(['event_id' => $event->id, 'title' => 'Atelier A', 'fee' => 10000, 'published' => true]);
    $b = Formation::create(['event_id' => $event->id, 'title' => 'Atelier B', 'fee' => 8000, 'published' => true]);

    $payA = paidPayment($event, $participant, ['flow' => 'formation', 'formation_id' => $a->id]);
    paidPayment($event, $participant, ['flow' => 'formation', 'formation_id' => $b->id], 8000);
    app(IssueAttestation::class)->handle($participant, 'formation', null, $a);
    app(IssueAttestation::class)->handle($participant, 'formation', null, $b);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payA))->assertRedirect();

    expect(Attestation::where('formation_id', $a->id)->first()->revoked)->toBeTrue();
    expect(Attestation::where('formation_id', $b->id)->first()->revoked)->toBeFalse();
});

it('un remboursement de formation ne touche pas la participation, et inversement', function () {
    $event = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($event);
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Atelier', 'fee' => 10000, 'published' => true]);

    $formationPay = paidPayment($event, $participant, ['flow' => 'formation', 'formation_id' => $formation->id]);
    paidPayment($event, $participant, [], 25000); // frais de participation, toujours réglés
    app(IssueAttestation::class)->handle($participant, 'participation');
    app(IssueAttestation::class)->handle($participant, 'formation', null, $formation);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $formationPay))->assertRedirect();

    expect(Attestation::where('type', 'formation')->first()->revoked)->toBeTrue();
    expect(Attestation::where('type', 'participation')->first()->revoked)->toBeFalse();
});

it('ne révoque pas l’attestation d’animation d’un formateur (il ne paie pas)', function () {
    $event = openEdition(['formation_enabled' => true]);
    [$user, $participant] = refundParticipant($event);
    $formation = Formation::create(['event_id' => $event->id, 'title' => 'Atelier', 'fee' => 10000, 'published' => true]);

    $payment = paidPayment($event, $participant, ['flow' => 'formation', 'formation_id' => $formation->id]);
    app(IssueAttestation::class)->handle($participant, 'formation_animation', null, $formation);

    $this->actingAs(adminStaff(), 'web')->post(route('admin.payments.refund', $payment))->assertRedirect();

    expect(Attestation::where('type', 'formation_animation')->first()->revoked)->toBeFalse();
});
