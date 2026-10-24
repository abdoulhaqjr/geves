<?php

use App\Actions\ConfirmPayment;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\Payment;
use App\Payments\GatewayRegistry;

// openEdition()/verifiedAccount() -> tests/Pest.php

it('affiche les formations de l’événement sélectionné (formation payante) sans erreur', function () {
    $event = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    $formation = publishedFormation($event, 10000);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee($formation->title);
});

it('n’expose pas la page Formations si le module est désactivé pour l’événement', function () {
    $event = openEdition();
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    publishedFormation($event, 0);
    $event->update(['settings' => array_merge($event->settings ?? [], ['formation_enabled' => false])]);

    $this->actingAs($user, 'account')->get(route('account.formations'))->assertNotFound();
});

it('n’expose pas la page Formations si l’événement n’a aucune formation publiée', function () {
    $event = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    publishedFormation($event, 0)->update(['published' => false]);

    $this->actingAs($user, 'account')->get(route('account.formations'))->assertNotFound();
});

it('ne liste pas les formations d’une autre événement que celle sélectionnée', function () {
    $event = openEdition(['formation_enabled' => true]);
    $other = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    $mine = publishedFormation($event, 0);
    $foreign = Formation::create(['event_id' => $other->id, 'title' => 'Formation d’ailleurs', 'fee' => 0, 'published' => true]);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee($mine->title)
        ->assertDontSee($foreign->title);
});

it('inscrit directement à une formation gratuite (sans paiement)', function () {
    $event = openEdition();
    $user = verifiedAccount();
    $formation = publishedFormation($event, 0);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation));

    $enr = FormationEnrollment::where('formation_id', $formation->id)->where('user_id', $user->id)->first();
    expect($enr)->not->toBeNull();
    expect($enr->status)->toBe('registered');
    expect($enr->payment_id)->toBeNull();
});

it('crée un paiement de nature formation pour une formation payante, réglé après confirmation', function () {
    $event = openEdition();
    $user = verifiedAccount();
    $formation = publishedFormation($event, 10000);

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
    $event = openEdition();
    $user = verifiedAccount();
    $participant = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author', 'user_id' => $user->id,
    ]);
    // Frais de participation déjà réglés.
    Payment::create([
        'event_id' => $event->id, 'participant_id' => $participant->id, 'amount' => 20000,
        'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now(),
    ]);

    $formation = publishedFormation($event, 10000);
    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation), ['gateway' => 'manual']);
    $enr = FormationEnrollment::where('formation_id', $formation->id)->first();
    $payment = Payment::find($enr->payment_id);

    // Doit passer malgré la participation déjà réglée (garde limitée à la participation).
    app(ConfirmPayment::class)->handle($payment);
    expect($payment->fresh()->status)->toBe('paid');
    expect($enr->fresh()->status)->toBe('paid');
});

it('conserve la garde anti-double sur les frais de participation', function () {
    $event = openEdition();
    $participant = $event->participants()->create([
        'first_name' => 'Ben', 'last_name' => 'Co', 'email' => 'ben@example.tg', 'role' => 'author',
    ]);
    Payment::create([
        'event_id' => $event->id, 'participant_id' => $participant->id, 'amount' => 20000,
        'status' => 'paid', 'gateway' => 'manual', 'method' => 'cash', 'paid_at' => now(),
    ]);
    $second = Payment::create([
        'event_id' => $event->id, 'participant_id' => $participant->id, 'amount' => 20000,
        'status' => 'pending', 'gateway' => 'manual', 'method' => 'cash',
    ]);

    expect(fn () => app(ConfirmPayment::class)->handle($second))->toThrow(DomainException::class);
});

/* ------------- La passerelle de l'événement vaut dès l'affichage ----------- */

/**
 * ⚠️⚠️ **LA CONFIGURATION DE L'ÉVÉNEMENT VAUT DÈS L'AFFICHAGE**, pas seulement
 * à l'inscription. `index()` ne l'appliquait pas : la page annonçait la
 * passerelle de la **plateforme** pendant qu'`enroll()` en employait une autre -
 * on cliquait « sur place » et on partait régler en ligne.
 *
 * ⚠️ Et c'est la passerelle RÉGLÉE, elle seule : un événement qui encaisse en
 * ligne ne propose pas le guichet ({@see GatewayRegistry::offered()}).
 */
it('propose à la formation la passerelle de l’événement, pas celle de la plateforme', function () {
    config([
        'payments.default' => 'manual',
        'services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test', 'webhook_secret' => 'whsec'],
    ]);

    $event = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    publishedFormation($event, 10000);
    $event->update(['settings' => [...$event->settings, 'payment' => ['gateway' => 'fedapay']]]);

    $this->actingAs($user, 'account')->get(route('account.formations'))
        ->assertOk()
        ->assertSee('Mobile money')
        ->assertDontSee('Paiement sur place / virement');
});

/** ⚠️ On ferme la porte, pas seulement le formulaire. */
it('refuse à la formation une passerelle qui n’est pas celle de l’événement', function () {
    config([
        'payments.default' => 'fedapay',
        'services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test', 'webhook_secret' => 'whsec'],
    ]);

    $event = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'author', 'user_id' => $user->id,
    ]);
    $formation = publishedFormation($event, 10000);

    $this->actingAs($user, 'account')
        ->from(route('account.formations'))
        ->post(route('account.formations.enroll', $formation), ['gateway' => 'manual'])
        ->assertSessionHasErrors('pay');

    expect(Payment::count())->toBe(0);
});
