<?php

use App\Actions\ConfirmPayment;
use App\Mail\PaymentReceiptMail;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\User;
use App\Support\Fees;
use Illuminate\Support\Facades\Mail;

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
    $account = User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'pass12345', 'grade' => 'Doctorant', 'affiliation' => 'UK',
    ]);
    $account->markEmailAsVerified();

    $participant = $edition->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
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

    $this->actingAs($staff, 'web')
        ->post(route('admin.payments.store'), [
            'edition_id' => (string) $edition->id,
            'first_name' => 'Yao', 'last_name' => 'Doe', 'email' => 'yao@example.tg',
            'amount' => '15000', 'method' => 'cash', 'markPaidNow' => '1',
        ])
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

    // La page s'ouvre et affiche le montant + les moyens de paiement.
    $this->actingAs($account, 'account')->get(route('account.payment', $edition))
        ->assertOk()
        ->assertSee('15 000');

    // Le règlement manuel enregistre un paiement en attente + des instructions.
    $this->actingAs($account, 'account')
        ->post(route('account.payment.pay', $edition), ['gateway' => 'manual'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('pay_instructions');

    $payment = Payment::first();
    expect($payment->status)->toBe('pending');
    expect($payment->participant_id)->toBe($participant->id);
});

it('n’ouvre pas le paiement sans communication acceptée', function () {
    $edition = payEdition();
    $account = User::create(['first_name' => 'Z', 'last_name' => 'Z', 'email' => 'z@example.tg', 'password' => 'pass12345']);
    $edition->participants()->create(['user_id' => $account->id, 'first_name' => 'Z', 'last_name' => 'Z', 'email' => $account->email]);

    // La page affiche le message d'inéligibilité et refuse le règlement.
    $this->actingAs($account, 'account')->get(route('account.payment', $edition))
        ->assertOk()
        ->assertSee('acceptée');

    $this->actingAs($account, 'account')
        ->post(route('account.payment.pay', $edition), ['gateway' => 'manual'])
        ->assertSessionHasErrors('pay');
});

it('attribue une référence unique à chaque paiement dès la création (avant confirmation)', function () {
    $edition = payEdition();
    [, $participant] = acceptedParticipant($edition);

    $p1 = Payment::create(['edition_id' => $edition->id, 'participant_id' => $participant->id, 'amount' => 15000, 'status' => 'pending', 'method' => 'manual']);
    $p2 = Payment::create(['edition_id' => $edition->id, 'participant_id' => $participant->id, 'amount' => 25000, 'status' => 'pending', 'method' => 'fedapay']);

    expect($p1->reference)->toStartWith('PAY-2026-');
    expect($p2->reference)->not->toBeNull();
    expect($p1->reference)->not->toBe($p2->reference); // unicité
    // La référence existe avant même la confirmation / le numéro de reçu.
    expect($p1->receipt_no)->toBeNull();

    // Le libellé de moyen reste générique (jamais le nom de l'agrégateur).
    expect($p2->methodLabel())->toBe('En ligne · Mobile money / carte');
});

it('la page paiements se cale par défaut sur le colloque courant (pas toutes les éditions)', function () {
    $c = Colloque::create(['name' => 'UK', 'slug' => 'uk-x']);
    $old = $c->editions()->create(['title' => 'UK 2025', 'year' => 2025, 'status' => 'closed', 'slug' => 'uk-2025']);
    $new = $c->editions()->create(['title' => 'UK 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'uk-2026']);

    $pOld = $old->participants()->create(['first_name' => 'A', 'last_name' => 'Ancien', 'email' => 'a@x.tg']);
    $pNew = $new->participants()->create(['first_name' => 'N', 'last_name' => 'Nouveau', 'email' => 'n@x.tg']);
    $old->payments()->create(['participant_id' => $pOld->id, 'amount' => 15000, 'status' => 'paid', 'reference' => 'PAY-OLD-0001']);
    $new->payments()->create(['participant_id' => $pNew->id, 'amount' => 15000, 'status' => 'paid', 'reference' => 'PAY-NEW-0001']);

    // Sans filtre → colloque courant (édition ouverte la plus récente) uniquement.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertSee('PAY-NEW-0001')
        ->assertDontSee('PAY-OLD-0001');

    // « Toutes les éditions » (edition_id explicitement vide) → tout.
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.payments.index', ['edition_id' => '']))
        ->assertOk()
        ->assertSee('PAY-NEW-0001')
        ->assertSee('PAY-OLD-0001');
});
