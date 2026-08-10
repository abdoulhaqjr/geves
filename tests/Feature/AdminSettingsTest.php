<?php

use App\Livewire\Admin\Settings\Index;
use App\Providers\AppServiceProvider;
use App\Support\Settings;
use App\Models\User;
use Livewire\Livewire;

// adminStaff() -> AdminSubmissionWorkflowTest

it('affiche les réglages au staff habilité', function () {
    $staff = adminStaff();

    Livewire::actingAs($staff, 'web')->test(Index::class)->assertOk()->assertSee('Général')->assertSee('SMTP');
});

it('enregistre les réglages', function () {
    $staff = adminStaff();

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->set('from_name', 'Colloques UK')
        ->set('from_address', 'colloques@univkara.tg')
        ->set('payments_default', 'cashpay')
        ->set('mail_host', 'smtp.univkara.tg')
        ->call('save')
        ->assertHasNoErrors();

    expect(Settings::get('mail.from_name'))->toBe('Colloques UK');
    expect(Settings::get('payments.default'))->toBe('cashpay');
    expect(Settings::get('mail.host'))->toBe('smtp.univkara.tg');
});

it('applique les réglages à la configuration au boot', function () {
    Settings::setMany([
        'payments.default' => 'fedapay',
        'mail.from_name' => 'GESCO Test',
        'mail.host' => 'smtp.test.tg',
        'mail.port' => '465',
        'cashpay.username' => 'user.demo',
    ]);

    // Invoque la logique d'application (méthode protégée du provider).
    $provider = new AppServiceProvider(app());
    (fn () => $this->applySettingsToConfig())->call($provider);

    expect(config('payments.default'))->toBe('fedapay');
    expect(config('mail.from.name'))->toBe('GESCO Test');
    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.test.tg');
    expect(config('services.cashpay.username'))->toBe('user.demo');
});

it('envoie un e-mail de test sans erreur', function () {
    $staff = adminStaff();

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->set('test_email', 'admin@univkara.tg')
        ->call('sendTest')
        ->assertHasNoErrors();
});

it('protège les réglages sans permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.settings'))->assertForbidden();
});
