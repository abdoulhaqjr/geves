<?php

use App\Actions\ReconcilePayments;
use App\Models\EventSeries;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Payments\Gateways\CashpayGateway;
use App\Payments\Gateways\FedaPayGateway;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
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

function configureCashpay(): void
{
    config(['services.cashpay' => [
        'base_url' => 'https://api.semoa-payments.ovh/sandbox-v3',
        'username' => 'user.demo', 'password' => 'secret',
        'client_id' => 'cashpay', 'client_secret' => 'cs',
        'api_key' => 'cashpay-sandbox-api-key-0123456789abcdef',
    ]]);
    Cache::flush();
}

function configureFedapay(): void
{
    config(['services.fedapay' => ['secret' => 'sk_test', 'mode' => 'test', 'webhook_secret' => 'whsec']]);
}

/* ------------------------------- CashPay ---------------------------------- */

it('CashPay : initie une facture et redirige vers bill_url', function () {
    configureCashpay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'status' => 'pending']);

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Pending']),
        '*/orders' => Http::response(['order_reference' => 'REF-1', 'bill_url' => 'https://lk.semoa.tg/abc', 'state' => 'Pending'], 201),
    ]);

    $result = (new CashpayGateway)->initiate($payment);

    expect($result['type'])->toBe('redirect');
    expect($result['url'])->toBe('https://lk.semoa.tg/abc');
    expect($payment->fresh()->gateway_ref)->toBe('REF-1');
});

it('CashPay : vérifie le statut (Paid → paid)', function () {
    configureCashpay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Paid', 'order_reference' => 'REF-1']),
    ]);

    expect((new CashpayGateway)->verify($payment))->toBe('paid');
});

it('CashPay : le webhook JWT valide confirme le paiement', function () {
    Mail::fake();
    configureCashpay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    $jwt = JWT::encode(['order_reference' => 'REF-1', 'state' => 'Paid'], 'cashpay-sandbox-api-key-0123456789abcdef', 'HS256');

    $this->post(route('payment.webhook', ['gateway' => 'cashpay']), ['token' => $jwt])->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->receipt_no)->not->toBeEmpty();
});

it('CashPay : le webhook mal signé ne confirme rien (fail-closed)', function () {
    configureCashpay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    $jwt = JWT::encode(['order_reference' => 'REF-1', 'state' => 'Paid'], 'wrong-key-9876543210zyxwvutsrqponmlkjihg', 'HS256');

    $this->post(route('payment.webhook', ['gateway' => 'cashpay']), ['token' => $jwt])->assertOk();

    expect($payment->fresh()->status)->toBe('pending'); // non confirmé
});

it('CashPay : le retour navigateur vérifie et confirme', function () {
    Mail::fake();
    configureCashpay();
    [$event, $account, $participant] = onlineSetup();
    $payment = $event->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Paid']),
    ]);

    $this->get(route('payment.return', ['gateway' => 'cashpay']).'?payment='.$payment->id)
        ->assertRedirect(route('account.payment', $event->id));

    expect($payment->fresh()->status)->toBe('paid');
});

it('CashPay : la page de paiement initie et redirige', function () {
    configureCashpay();
    [$event, $account, $participant] = onlineSetup();

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Pending']),
        '*/orders' => Http::response(['order_reference' => 'REF-9', 'bill_url' => 'https://lk.semoa.tg/pay9'], 201),
    ]);

    $this->actingAs($account, 'account')
        ->post(route('account.payment.pay', $event), ['gateway' => 'cashpay'])
        ->assertRedirect('https://lk.semoa.tg/pay9');

    expect(Payment::where('gateway', 'cashpay')->first()->gateway_ref)->toBe('REF-9');
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

it('FedaPay : le marchand d’une événement ne peut pas confirmer le paiement d’une AUTRE événement', function () {
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
