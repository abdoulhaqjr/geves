<?php

use App\Models\Account;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use Illuminate\Support\Str;
use App\Models\User;

// adminStaff() -> AdminSubmissionWorkflowTest

function pdfAttestation(): Attestation
{
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();

    return Attestation::create([
        'uuid' => (string) Str::uuid(),
        'edition_id' => $edition->id,
        'participant_id' => $participant->id,
        'type' => 'participation',
        'recipient_name' => $participant->full_name,
        'recipient_email' => $participant->email,
        'revoked' => false,
        'issued_at' => now(),
    ]);
}

it('génère un vrai PDF d’attestation (avec QR)', function () {
    $attestation = pdfAttestation();

    $response = $this->get(route('attestation.pdf', $attestation->uuid));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
});

it('génère un vrai PDF de reçu côté back-office', function () {
    $staff = adminStaff();
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();
    $payment = Payment::factory()->for($edition)->for($participant)->paid()->create();

    $response = $this->actingAs($staff, 'web')
        ->get(route('admin.payments.receipt', ['payment' => $payment, 'pdf' => 1]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
});

it('sert le reçu en HTML sans le paramètre pdf', function () {
    $staff = adminStaff();
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create();
    $payment = Payment::factory()->for($edition)->for($participant)->paid()->create();

    $this->actingAs($staff, 'web')
        ->get(route('admin.payments.receipt', $payment))
        ->assertOk()
        ->assertSee($payment->receipt_no);
});

it('génère le PDF de reçu du participant propriétaire', function () {
    $account = User::factory()->create();
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create(['user_id' => $account->id]);
    $payment = Payment::factory()->for($edition)->for($participant)->paid()->create();

    $response = $this->actingAs($account, 'account')
        ->get(route('account.receipt', ['payment' => $payment, 'pdf' => 1]));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('refuse le reçu PDF d’un autre participant', function () {
    $account = User::factory()->create();
    $edition = Edition::factory()->create();
    $participant = Participant::factory()->for($edition)->create(); // sans account_id
    $payment = Payment::factory()->for($edition)->for($participant)->paid()->create();

    $this->actingAs($account, 'account')
        ->get(route('account.receipt', ['payment' => $payment, 'pdf' => 1]))
        ->assertNotFound();
});
