<?php

use App\Actions\ConfirmPayment;
use App\Livewire\Account\Payment\Pay;
use App\Livewire\Admin\Payments\Record;
use App\Mail\PaymentReceiptMail;
use App\Models\Account;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Payment;
use App\Support\Fees;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest

function payEdition(): Edition
{
    $colloque = Colloque::create(['name' => 'Colloque UK', 'slug' => 'uk']);

    return $colloque->editions()->create([
        'title' => 'UK 2026', 'edition_label' => '3e', 'year' => 2026,
        'status' => 'open', 'slug' => 'uk-2026',
        'settings' => ['fees' => [
            ['label' => 'Doctorant', 'amount' => 15000],
            ['label' => 'Enseignant-chercheur', 'amount' => 25000],
        ]],
    ]);
}

function acceptedParticipant(Edition $edition): array
{
    $account = Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'pass12345', 'grade' => 'Doctorant', 'affiliation' => 'UK',
    ]);
    $account->markEmailAsVerified();

    $participant = $edition->participants()->create([
        'account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'title' => 'Doctorant', 'role' => 'author',
    ]);
    $edition->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'T', 'status' => 'accepted',
    ]);

    return [$account, $participant];
}

/* ------------------------------- Domaine ---------------------------------- */

it('résout le montant des frais selon le grade', function () {
    $edition = payEdition();

    expect(Fees::resolveAmount($edition, 'Doctorant'))->toBe(15000.0);
    expect(Fees::resolveAmount($edition, 'enseignant-chercheur'))->toBe(25000.0); // insensible à la casse
    expect(Fees::resolveAmount($edition, 'Inconnu'))->toBeNull();
    expect(Fees::highestAmount($edition))->toBe(25000.0);
});

/* ----------------------------- Encaissement ------------------------------- */

it('enregistre un encaissement manuel, génère le reçu et notifie', function () {
    Mail::fake();
    $staff = adminStaff();
    $edition = payEdition();

    Livewire::actingAs($staff, 'web')
        ->test(Record::class)
        ->set('edition_id', (string) $edition->id)
        ->set('first_name', 'Yao')->set('last_name', 'Doe')->set('email', 'yao@example.tg')
        ->set('amount', '15000')->set('method', 'cash')->set('markPaidNow', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.payments.index'));

    $payment = Payment::first();
    expect($payment->status)->toBe('paid');
    expect($payment->receipt_no)->not->toBeEmpty();
    Mail::assertQueued(PaymentReceiptMail::class, fn ($m) => $m->payment->is($payment));
});

it('refuse un second paiement pour un participant déjà réglé (garde-fou)', function () {
    $edition = payEdition();
    [$account, $participant] = acceptedParticipant($edition);
    $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'status' => 'paid']);

    $second = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'status' => 'pending']);

    expect(fn () => (new ConfirmPayment)->handle($second))->toThrow(DomainException::class);
    expect(Payment::where('status', 'paid')->count())->toBe(1);
});

it('sert le reçu imprimable pour un paiement réglé et 404 sinon', function () {
    $staff = adminStaff();
    $edition = payEdition();
    [$account, $participant] = acceptedParticipant($edition);
    $paid = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'status' => 'paid', 'receipt_no' => 'REC-2026-0001']);
    $pending = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'status' => 'pending']);

    $this->actingAs($staff, 'web')->get(route('admin.payments.receipt', $paid))->assertOk()->assertSee('REC-2026-0001');
    $this->actingAs($staff, 'web')->get(route('admin.payments.receipt', $pending))->assertNotFound();
});

/* ----------------------------- Participant -------------------------------- */

it('ouvre le paiement participant quand une communication est acceptée', function () {
    $edition = payEdition();
    [$account, $participant] = acceptedParticipant($edition);

    $c = Livewire::actingAs($account, 'account')->test(Pay::class, ['edition' => $edition]);
    expect($c->instance()->isEligible())->toBeTrue();
    expect($c->instance()->amount)->toBe(15000.0);

    $c->call('pay', 'manual')->assertHasNoErrors()->assertSet('instructions', fn ($v) => filled($v));

    $payment = Payment::first();
    expect($payment->status)->toBe('pending');
    expect($payment->participant_id)->toBe($participant->id);
});

it('n’ouvre pas le paiement sans communication acceptée', function () {
    $edition = payEdition();
    $account = Account::create(['first_name' => 'Z', 'last_name' => 'Z', 'email' => 'z@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Z', 'last_name' => 'Z', 'email' => $account->email]);

    $c = Livewire::actingAs($account, 'account')->test(Pay::class, ['edition' => $edition]);
    expect($c->instance()->isEligible())->toBeFalse();
});
