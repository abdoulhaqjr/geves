<?php

use App\Livewire\Account\Payment\Pay;
use App\Models\Account;
use App\Models\Colloque;
use App\Models\Payment;
use App\Payments\Gateways\CashpayGateway;
use App\Payments\Gateways\FedaPayGateway;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function onlineSetup(): array
{
    $colloque = Colloque::create(['name' => 'UK', 'slug' => 'uk']);
    $edition = $colloque->editions()->create(['title' => 'UK 2026', 'year' => 2026, 'status' => 'open', 'slug' => 'uk-2026',
        'settings' => ['fees' => [['label' => 'Doctorant', 'amount' => 15000]]]]);
    $account = Account::create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345', 'affiliation' => 'UK', 'grade' => 'Doctorant']);
    $account->markEmailAsVerified();
    $participant = $edition->participants()->create(['account_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $account->email, 'phone' => '+22890000000', 'title' => 'Doctorant']);
    $edition->submissions()->create(['participant_id' => $participant->id, 'ref_code' => '26-001', 'title' => 'T', 'status' => 'accepted']);

    return [$edition, $account, $participant];
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
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'status' => 'pending']);

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
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Paid', 'order_reference' => 'REF-1']),
    ]);

    expect((new CashpayGateway)->verify($payment))->toBe('paid');
});

it('CashPay : le webhook JWT valide confirme le paiement', function () {
    Mail::fake();
    configureCashpay();
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    $jwt = JWT::encode(['order_reference' => 'REF-1', 'state' => 'Paid'], 'cashpay-sandbox-api-key-0123456789abcdef', 'HS256');

    $this->post(route('payment.webhook', ['gateway' => 'cashpay']), ['token' => $jwt])->assertOk();

    expect($payment->fresh()->status)->toBe('paid');
    expect($payment->fresh()->receipt_no)->not->toBeEmpty();
});

it('CashPay : le webhook mal signé ne confirme rien (fail-closed)', function () {
    configureCashpay();
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    $jwt = JWT::encode(['order_reference' => 'REF-1', 'state' => 'Paid'], 'wrong-key-9876543210zyxwvutsrqponmlkjihg', 'HS256');

    $this->post(route('payment.webhook', ['gateway' => 'cashpay']), ['token' => $jwt])->assertOk();

    expect($payment->fresh()->status)->toBe('pending'); // non confirmé
});

it('CashPay : le retour navigateur vérifie et confirme', function () {
    Mail::fake();
    configureCashpay();
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'cashpay', 'gateway_ref' => 'REF-1', 'status' => 'pending']);

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Paid']),
    ]);

    $this->get(route('payment.return', ['gateway' => 'cashpay']).'?payment='.$payment->id)
        ->assertRedirect(route('account.payment', $edition->id));

    expect($payment->fresh()->status)->toBe('paid');
});

it('CashPay : le composant Pay initie et redirige', function () {
    configureCashpay();
    [$edition, $account, $participant] = onlineSetup();

    Http::fake([
        '*/auth' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
        '*/orders/*' => Http::response(['state' => 'Pending']),
        '*/orders' => Http::response(['order_reference' => 'REF-9', 'bill_url' => 'https://lk.semoa.tg/pay9'], 201),
    ]);

    Livewire::actingAs($account, 'account')
        ->test(Pay::class, ['edition' => $edition])
        ->call('pay', 'cashpay')
        ->assertRedirect('https://lk.semoa.tg/pay9');

    expect(Payment::where('gateway', 'cashpay')->first()->gateway_ref)->toBe('REF-9');
});

/* ------------------------------- FedaPay ---------------------------------- */

it('FedaPay : initie une transaction et redirige vers l’URL hébergée', function () {
    configureFedapay();
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'status' => 'pending']);

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
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

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
    [$edition, $account, $participant] = onlineSetup();
    $payment = $edition->payments()->create(['participant_id' => $participant->id, 'amount' => 15000, 'gateway' => 'fedapay', 'gateway_ref' => '4242', 'status' => 'pending']);

    $body = json_encode(['entity' => ['id' => 4242, 'status' => 'approved']]);

    $this->call('POST', route('payment.webhook', ['gateway' => 'fedapay']), [], [], [],
        ['HTTP_X_FEDAPAY_SIGNATURE' => 't=1,s=deadbeef', 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($payment->fresh()->status)->toBe('pending');
});
