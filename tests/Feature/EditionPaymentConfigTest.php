<?php

use App\Support\EventPaymentConfig;

// Fabriques partagées : openEdition(), adminStaff() (tests/Pest.php).

it('surcharge la config de paiement à partir des réglages de l’événement', function () {
    config(['payments.default' => 'manual', 'services.fedapay.secret' => 'global_secret', 'services.fedapay.mode' => 'live']);

    $event = openEdition();
    $event->update(['settings' => array_merge($event->settings ?? [], [
        'payment' => ['gateway' => 'fedapay', 'fedapay' => ['secret' => 'edition_secret']],
    ])]);

    EventPaymentConfig::apply($event->fresh());

    expect(config('payments.default'))->toBe('fedapay');
    expect(config('services.fedapay.secret'))->toBe('edition_secret');
    expect(config('services.fedapay.mode'))->toBe('live'); // champ vide → repli global
});

it('conserve la config globale quand l’événement n’a pas de réglage paiement', function () {
    config(['services.fedapay.secret' => 'global_secret']);

    EventPaymentConfig::apply(openEdition());

    expect(config('services.fedapay.secret'))->toBe('global_secret');
});

it('permet au staff d’enregistrer la passerelle de paiement d’un événement', function () {
    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.evenements.payment', $event), [
            'gateway' => 'fedapay',
            'fedapay' => ['secret' => 'sk_test_x', 'mode' => 'test', 'webhook_secret' => ''],
        ])
        ->assertRedirect();

    $event->refresh();
    expect($event->settings['payment']['gateway'])->toBe('fedapay');
    expect($event->settings['payment']['fedapay']['secret'])->toBe('sk_test_x');
    expect($event->settings['payment']['fedapay'])->not->toHaveKey('webhook_secret'); // vide non stocké
});
