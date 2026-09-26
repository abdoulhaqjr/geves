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

it('distingue la nature des paiements et nomme la formation concernée', function () {
    $edition = openEdition(['formation_enabled' => true]);
    $formation = publishedFormation($edition, 10000);

    $participation = Payment::create(['edition_id' => $edition->id, 'amount' => 1, 'method' => 'cash', 'gateway' => 'manual']);
    $forma = Payment::create([
        'edition_id' => $edition->id, 'amount' => 2, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation', 'formation_id' => $formation->id],
    ]);
    $co = Payment::create([
        'edition_id' => $edition->id, 'amount' => 3, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'coauthor'],
    ]);

    expect($participation->flowLabel())->toBe('Participation');
    expect($participation->flowDetail())->toBeNull();
    expect($forma->flowLabel())->toBe('Formation');
    expect($forma->flowDetail())->toBe($formation->title);
    expect($co->flowLabel())->toBe('Co-auteur');
});

it('filtre les paiements par nature sur la page admin', function () {
    $edition = openEdition(['formation_enabled' => true]);
    $formation = publishedFormation($edition, 10000);
    Payment::create(['edition_id' => $edition->id, 'amount' => 111, 'method' => 'cash', 'gateway' => 'manual']);
    Payment::create([
        'edition_id' => $edition->id, 'amount' => 222, 'method' => 'fedapay', 'gateway' => 'fedapay',
        'meta' => ['flow' => 'formation', 'formation_id' => $formation->id],
    ]);

    $admin = adminStaff();

    $this->actingAs($admin, 'web')->get(route('admin.payments.index', ['edition_id' => $edition->id, 'flow' => 'formation']))
        ->assertOk()->assertSee('222')->assertDontSee('111');

    $this->actingAs($admin, 'web')->get(route('admin.payments.index', ['edition_id' => $edition->id, 'flow' => 'participation']))
        ->assertOk()->assertSee('111')->assertDontSee('222');
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
