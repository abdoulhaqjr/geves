<?php

use App\Models\Payment;
use App\Payments\GatewayRegistry;
use App\Payments\Gateways\CashpayGateway;
use App\Payments\Gateways\FedaPayGateway;

// openEdition()/verifiedAccount() -> tests/Pest.php

it('n’expose jamais le nom de l’agrégateur dans le libellé d’un moyen de paiement', function () {
    foreach (app(GatewayRegistry::class)->all() as $gateway) {
        expect($gateway->label())
            ->not->toContain('FedaPay')
            ->not->toContain('CashPay')
            ->not->toContain('SEMOA');
    }
});

it('propose des opérateurs mobile money ciblables', function () {
    $operators = (new FedaPayGateway)->mobileOperators();

    expect($operators)->toHaveKey('togocel');
    expect($operators['togocel'])->not->toContain('FedaPay');
    // La passerelle qui ne sait pas cibler l'opérateur renvoie une liste vide.
    expect((new CashpayGateway)->mobileOperators())->toBe([]);
});

it('mémorise l’opérateur choisi sur le paiement et l’oublie si vidé', function () {
    $edition = openEdition();
    $payment = Payment::create([
        'edition_id' => $edition->id, 'amount' => 5000, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation'],
    ]);

    $payment->setOperator('togocel');
    expect($payment->fresh()->meta['operator'])->toBe('togocel');
    // Le flux d'origine est préservé.
    expect($payment->fresh()->meta['flow'])->toBe('formation');

    $payment->setOperator('');
    expect($payment->fresh()->meta)->not->toHaveKey('operator');
});

it('enregistre l’opérateur choisi lors de l’inscription payante à une formation', function () {
    $edition = openEdition(['formation_enabled' => true]);
    $user = verifiedAccount();
    $edition->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => $user->email, 'role' => 'trainee', 'user_id' => $user->id,
    ]);
    $formation = publishedFormation($edition, 10000);

    $this->actingAs($user, 'account')->post(route('account.formations.enroll', $formation), [
        'gateway' => 'manual', 'operator' => 'moov_tg',
    ]);

    expect(Payment::first()->meta['operator'])->toBe('moov_tg');
});
