<?php

use App\Support\EditionPaymentConfig;

// Fabriques partagées : openEdition(), adminStaff() (tests/Pest.php).

it('surcharge la config de paiement à partir des réglages de l’édition', function () {
    config(['payments.default' => 'manual', 'services.fedapay.secret' => 'global_secret', 'services.fedapay.mode' => 'live']);

    $edition = openEdition();
    $edition->update(['settings' => array_merge($edition->settings ?? [], [
        'payment' => ['gateway' => 'fedapay', 'fedapay' => ['secret' => 'edition_secret']],
    ])]);

    EditionPaymentConfig::apply($edition->fresh());

    expect(config('payments.default'))->toBe('fedapay');
    expect(config('services.fedapay.secret'))->toBe('edition_secret');
    expect(config('services.fedapay.mode'))->toBe('live'); // champ vide → repli global
});

it('conserve la config globale quand l’édition n’a pas de réglage paiement', function () {
    config(['services.fedapay.secret' => 'global_secret']);

    EditionPaymentConfig::apply(openEdition());

    expect(config('services.fedapay.secret'))->toBe('global_secret');
});

it('permet au staff d’enregistrer la passerelle de paiement d’une édition', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.editions.payment', $edition), [
            'gateway' => 'fedapay',
            'fedapay' => ['secret' => 'sk_test_x', 'mode' => 'test', 'webhook_secret' => ''],
            'cashpay' => [],
        ])
        ->assertRedirect();

    $edition->refresh();
    expect($edition->settings['payment']['gateway'])->toBe('fedapay');
    expect($edition->settings['payment']['fedapay']['secret'])->toBe('sk_test_x');
    expect($edition->settings['payment']['fedapay'])->not->toHaveKey('webhook_secret'); // vide non stocké
});
