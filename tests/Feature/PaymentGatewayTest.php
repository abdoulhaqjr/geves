<?php

use App\Actions\ReconcilePayments;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\User;
use App\Payments\Gateways\FedaPayGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function onlineSetup(): array
{
    $series = EventSeries::create(['name' => 'UK', 'slug' => 'uk']);
    $event = $series->events()->create(['title' => 'UK 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'uk-2026',
        'settings' => ['fees' => [['label' => 'Doctorant', 'amount' => 15000]]]]);
    $account = User::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345', 'affiliation' => 'UK', 'grade' => 'Doctorant']);
    $account->markEmailAsVerified();
    $participant = $event->participants()->create(['user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'phone' => '+22890000000', 'title' => 'Doctorant']);
    $event->submissions()->create(['participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'T', 'status' => 'accepted']);

    return [$event, $account, $participant];
}

function configureFedapay(): void
{
    config(['services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test', 'webhook_secret' => 'whsec']]);
}

/* ------------------------- Moyens de paiement ----------------------------- */

/**
 * Le corps de la requête de CRÉATION de transaction, ou null.
 *
 * ⚠️ **On lit la requête, on ne la fait pas valider par un rappel.**
 * `Http::assertSent` réussit dès qu'UNE requête satisfait le rappel : un rappel
 * qui laisse passer les autres appels (ici la demande de jeton) est donc
 * toujours satisfait, et le cas ne mord jamais. Mesuré : en envoyant « card »
 * dans `mode`, la garde restait verte.
 *
 * @return array<string, mixed>|null
 */
function corpsDeCreation(): ?array
{
    foreach (Http::recorded() as [$request, $response]) {
        if (str_ends_with($request->url(), '/transactions')) {
            return $request->data();
        }
    }

    return null;
}

/**
 * ⚠️ **La carte bancaire était acceptée sans être proposée.** La page de
 * paiement ne listait que des opérateurs mobile money, sous l'intitulé
 * « Choisir mon opérateur » : rien ne disait qu'on pouvait régler par carte, et
 * tout laissait croire qu'il fallait prendre un opérateur. Elle fonctionnait
 * pourtant - en ne choisissant rien, la page du prestataire propose tout ce que
 * le compte marchand accepte.
 */
it('propose la carte bancaire en plus du mobile money', function () {
    $moyens = (new FedaPayGateway)->paymentMethods();

    expect($moyens)->toHaveKey('card');
    expect($moyens['card'])->toContain('Carte');
    // ⚠️ Convention du projet : jamais le nom de l'agrégateur devant le payeur.
    expect($moyens['card'])->not->toContain('FedaPay');
    // Les opérateurs restent proposés à côté.
    expect($moyens)->toHaveKey('togocel');
});

/**
 * ⚠️ **Le code « carte » ne quitte JAMAIS la plateforme.** Le champ `mode` de la
 * requête ne désigne que des opérateurs mobile money ; aucun code carte n'est
 * documenté. En l'omettant, la page hébergée propose tout ce que le compte
 * marchand accepte - carte comprise, ce qu'on veut. Un code inventé ferait
 * échouer la création de la transaction, donc le paiement ENTIER : le payeur
 * n'aurait pas une page sans carte, il n'aurait pas de page du tout.
 */
it('n’envoie aucun mode au prestataire quand le payeur annonce la carte', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000,
        'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'pending',
    ]);
    $payment->setOperator('card');

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 42]], 201),
    ]);

    (new FedaPayGateway)->initiate($payment);

    $corps = corpsDeCreation();

    expect($corps)->not->toBeNull('aucune transaction créée');
    expect($corps)->not->toHaveKey('mode');
});

/** Un opérateur mobile money, lui, EST transmis : c'est tout son intérêt. */
it('transmet l’opérateur mobile money choisi', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create([
        'participant_id' => $participant->id, 'amount' => 15000,
        'method' => 'fedapay', 'gateway' => 'fedapay', 'status' => 'pending',
    ]);
    $payment->setOperator('moov_tg');

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://paiement.exemple/abc']),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 43]], 201),
    ]);

    (new FedaPayGateway)->initiate($payment);

    expect(corpsDeCreation()['mode'] ?? null)->toBe('moov_tg');
});

/**
 * ⚠️ **C'est le compte marchand qui accepte la carte, pas nous.** Elle demande
 * une couverture accordée sur demande ; sans elle, l'annoncer enverrait le
 * payeur sur une page qui ne la propose pas. D'où le réglage.
 */
it('retire la carte des moyens proposés quand le réglage la ferme', function () {
    configureFedapay();
    config(['services.fedapay.card' => false]);

    $moyens = (new FedaPayGateway)->paymentMethods();

    expect($moyens)->not->toHaveKey('card');
    expect($moyens)->toHaveKey('togocel'); // le mobile money n'est pas touché
});

/** La page de paiement du participant doit la MONTRER, pas seulement l'accepter. */
it('offre la carte sur la page de paiement du participant', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();

    $this->actingAs($account, 'account')
        ->get(route('account.payment', $event->id))
        ->assertOk()
        ->assertSee('Carte bancaire (Visa / Mastercard)')
        // ⚠️ Le choix n'est pas obligatoire, et l'intitulé doit le dire : « Choisir
        // mon opérateur » laissait croire le contraire.
        ->assertSee('Je choisis sur la page de paiement')
        ->assertDontSee('Choisir mon opérateur');
});

/* ------------------------------- FedaPay ---------------------------------- */

it('FedaPay : initie une transaction et redirige vers l’URL hébergée', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'status' => 'pending']);

    Http::fake([
        '*/transactions/*/token' => Http::response(['url' => 'https://checkout.fedapay.com/xyz']),
        '*/transactions/*' => Http::response(['v1/transaction' => ['status' => 'approved']]),
        '*/transactions' => Http::response(['v1/transaction' => ['id' => 4242]], 201),
    ]);

    $result = (new FedaPayGateway)->initiate($payment);

    expect($result['url'])->toBe('https://checkout.fedapay.com/xyz');
    expect($payment->fresh()->gateway_ref)->toBe('4242');
});

it('FedaPay : le webhook signé HMAC confirme le paiement', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    $body = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => 4242, 'status' => 'approved']]);
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, 'whsec');
    $header = "t={$ts},s={$sig}";

    $this->call('POST', route('payment.webhook', ['gateway' => 'fedapay']), [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('FedaPay : webhook sans signature valide ne confirme rien', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    $body = json_encode(['entity' => ['id' => 4242, 'status' => 'approved']]);

    $this->call('POST', route('payment.webhook', ['gateway' => 'fedapay']), [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => 't=1,s=deadbeef', 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($payment->fresh()->status)->toBe('pending');
});

/* ------------------- Webhook & passerelle propre à l'événement ---------------- */

/** Configure l'événement avec SON propre compte FedaPay (secrets différents du global). */
function fedapayForEdition(Event $event, string $secret = 'sk_edition', string $webhook = 'wh_edition'): void
{
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'payment' => ['gateway' => 'fedapay', 'fedapay' => ['secret' => $secret, 'mode' => 'test', 'webhook_secret' => $webhook]],
    ])]);
}

/** Forge une requête de webhook FedaPay signée avec le secret donné. */
function fedapayWebhook(string $url, int $reference, string $secret, string $status = 'approved'): TestResponse
{
    $body = json_encode(['name' => 'transaction.approved', 'entity' => ['id' => $reference, 'status' => $status]]);
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, $secret);

    return test()->call('POST', $url, [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => "t={$ts},s={$sig}", 'CONTENT_TYPE' => 'application/json'], $body);
}

it('FedaPay : un webhook signé avec le secret PROPRE à l’événement confirme le paiement', function () {
    Mail::fake();
    configureFedapay(); // global : « whsec »
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event); // l'événement signe avec « wh_edition »
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '777', 'status' => 'pending']);

    fedapayWebhook(route('payment.webhook', ['gateway' => 'fedapay']), 777, 'wh_edition')->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('FedaPay : l’URL de webhook dédiée à l’événement confirme aussi le paiement', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event);
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '778', 'status' => 'pending']);

    fedapayWebhook(route('payment.webhook.edition', ['gateway' => 'fedapay', 'event' => $event]), 778, 'wh_edition')->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
});

it('FedaPay : le marchand d’un événement ne peut pas confirmer le paiement d’une AUTRE événement', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event); // événement A : « wh_edition »

    // Paiement rattaché à une AUTRE événement (compte global).
    $autre = $event->series->events()->create(['title' => 'UK 2027', 'year' => 2027, 'status' => 'open', 'slug' => 'uk-2027']);
    $payment = $autre->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '999', 'status' => 'pending']);

    fedapayWebhook(route('payment.webhook', ['gateway' => 'fedapay']), 999, 'wh_edition')->assertOk();

    expect($payment->fresh()->status)->toBe('pending'); // ignoré
});

it('Réconciliation : confirme un paiement réglé dont le webhook n’est jamais arrivé', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    Http::fake(['*/transactions/4242' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    $report = app(ReconcilePayments::class)->handle();

    expect($report['paid'])->toBe(1);
    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->receipt_no)->not->toBeEmpty();
});

it('Réconciliation : interroge la passerelle avec les identifiants de l’ÉDITION', function () {
    Mail::fake();
    configureFedapay(); // global : « sk_test »
    [$event, $account, $participant] = onlineSetup();
    fedapayForEdition($event); // événement : « sk_edition »
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4243', 'status' => 'pending']);

    Http::fake(['*/transactions/4243' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    app(ReconcilePayments::class)->handle();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk_edition'));
    expect($payment->fresh()->status)->toBe('paid');
});

it('Réconciliation : laisse « en attente » un paiement non abouti', function () {
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4244', 'status' => 'pending']);

    Http::fake(['*/transactions/4244' => Http::response(['v1/transaction' => ['status' => 'pending']])]);

    $report = app(ReconcilePayments::class)->handle();

    expect($report['paid'])->toBe(0);
    expect($payment->fresh()->status)->toBe('pending');
});

it('Back-office : « Revérifier » confirme un paiement réellement réglé', function () {
    Mail::fake();
    configureFedapay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '5555', 'status' => 'pending']);

    Http::fake(['*/transactions/5555' => Http::response(['v1/transaction' => ['status' => 'approved']])]);

    $this->actingAs(adminStaff())
        ->post(route('admin.payments.recheck', $payment))
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe('paid');
});
