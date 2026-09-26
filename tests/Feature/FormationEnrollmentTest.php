<?php

use App\Actions\ConfirmPayment;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Payment;

// openEdition()/verifiedAccount() -> tests/Pest.php

/**
 * Formation publiée + module Formations activé sur son édition : c'est
 * l'invariant réel (une formation n'est proposée que si le module est activé
 * pour l'édition, cf. Formation::offeredIn).
 */
function publishedFormation($edition, float $fee): Formation
{
    $edition->update(['settings' => array_merge($edition->settings ?? [], ['formation_enabled' => true])]);

    return Formation::create([
        'edition_id' => $edition->id,
        'title' => $fee > 0 ? 'Formation payante' : 'Formation gratuite',
        'fee' => $fee,
        'published' => true,
    ]);
}

it('affiche les formations de l’édition sélectionnée (formation payante) sans erreur', function () {
    $edition = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    $formation = publishedFormation($edition, 10000);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee($formation->title);
});

it('n’expose pas la page Formations si le module est désactivé pour l’édition', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    publishedFormation($edition, 0);
    $edition->update(['settings' => array_merge($edition->settings ?? [], ['formation_enabled' => false])]);

    $this->actingAs($user, 'account')->get(route('account.formations'))->assertNotFound();
});

it('n’expose pas la page Formations si l’édition n’a aucune formation publiée', function () {
    $edition = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    publishedFormation($edition, 0)->update(['published' => false]);

    $this->actingAs($user, 'account')->get(route('account.formations'))->assertNotFound();
});

it('ne liste pas les formations d’une autre édition que celle sélectionnée', function () {
    $edition = openEdition(['formation_enabled' => true]);
    $other = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    $mine = publishedFormation($edition, 0);
    $foreign = Formation::create(['edition_id' => $other->id, 'title' => 'Formation d’ailleurs', 'fee' => 0, 'published' => true]);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee($mine->title)
        ->assertDontSee($foreign->title);
});

it('inscrit directement à une formation gratuite (sans paiement)', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    $formation = publishedFormation($edition, 0);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation));

    $enr = FormationEnrollment::where('formation_id', $formation->id)->where('user_id', $user->id)->first();
    expect($enr)->not->toBeNull();
    expect($enr->status)->toBe('registered');
    expect($enr->payment_id)->toBeNull();
});

it('crée un paiement de nature formation pour une formation payante, réglé après confirmation', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    $formation = publishedFormation($edition, 10000);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation), ['gateway' => 'manual']);

    $enr = FormationEnrollment::where('formation_id', $formation->id)->where('user_id', $user->id)->first();
    expect($enr->status)->toBe('registered');

    $payment = Payment::find($enr->payment_id);
    expect($payment)->not->toBeNull();
    expect($payment->flow())->toBe('formation');
    expect((int) $payment->meta['formation_id'])->toBe($formation->id);

    // Confirmation (retour/webhook/manuel passent tous par ConfirmPayment).
    app(ConfirmPayment::class)->handle($payment);
    expect($enr->fresh()->status)->toBe('paid');
});

it('n’est pas bloqué par la garde anti-double : participation payée + formation payée coexistent', function () {
    $edition = openEdition();
    $user = verifiedAccount();
    $participant = $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author', 'user_id' => $user->id,
    ]);
    // Frais de participation déjà réglés.
    Payment::create([
        'edition_id' => $edition->id, 'participant_id' => $participant->id, 'amount' => 20000,
        'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now(),
    ]);

    $formation = publishedFormation($edition, 10000);
    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation), ['gateway' => 'manual']);
    $enr = FormationEnrollment::where('formation_id', $formation->id)->first();
    $payment = Payment::find($enr->payment_id);

    // Doit passer malgré la participation déjà réglée (garde limitée à la participation).
    app(ConfirmPayment::class)->handle($payment);
    expect($payment->fresh()->status)->toBe('paid');
    expect($enr->fresh()->status)->toBe('paid');
});

it('conserve la garde anti-double sur les frais de participation', function () {
    $edition = openEdition();
    $participant = $edition->participants()->create([
        'first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'role' => 'author',
    ]);
    Payment::create([
        'edition_id' => $edition->id, 'participant_id' => $participant->id, 'amount' => 20000,
        'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now(),
    ]);
    $second = Payment::create([
        'edition_id' => $edition->id, 'participant_id' => $participant->id, 'amount' => 20000,
        'status' => 'pending', 'gateway' => 'manual', 'method' => 'cash',
    ]);

    expect(fn () => app(ConfirmPayment::class)->handle($second))->toThrow(DomainException::class);
});
