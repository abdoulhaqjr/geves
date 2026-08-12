<?php

use App\Livewire\Admin\Settings\Index;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Settings;
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
        ->set('ai_default', 'grok')
        ->set('ai_anthropic_key', 'sk-ant-xyz')
        ->set('ai_anthropic_model', 'claude-sonnet-4-5')
        ->set('ai_grok_key', 'xai-123')
        ->set('ai_grok_model', 'grok-4')
        ->call('save')
        ->assertHasNoErrors();

    expect(Settings::get('mail.from_name'))->toBe('Colloques UK');
    expect(Settings::get('payments.default'))->toBe('cashpay');
    expect(Settings::get('mail.host'))->toBe('smtp.univkara.tg');
    expect(Settings::get('ai.default'))->toBe('grok');
    expect(Settings::get('ai.anthropic_key'))->toBe('sk-ant-xyz');
    expect(Settings::get('ai.anthropic_model'))->toBe('claude-sonnet-4-5');
    expect(Settings::get('ai.grok_key'))->toBe('xai-123');
});

it('applique les réglages à la configuration au boot', function () {
    Settings::setMany([
        'payments.default' => 'fedapay',
        'mail.from_name' => 'GESCO Test',
        'mail.host' => 'smtp.test.tg',
        'mail.port' => '465',
        'cashpay.username' => 'user.demo',
        'ai.default' => 'gemini',
        'ai.anthropic_key' => 'sk-ant-abc',
        'ai.grok_key' => 'xai-abc',
        'ai.grok_model' => 'grok-4',
    ]);

    // Invoque la logique d'application (méthode protégée du provider).
    $provider = new AppServiceProvider(app());
    (fn () => $this->applySettingsToConfig())->call($provider);

    expect(config('payments.default'))->toBe('fedapay');
    expect(config('mail.from.name'))->toBe('GESCO Test');
    expect(config('mail.default'))->toBe('smtp');
    expect(config('mail.mailers.smtp.host'))->toBe('smtp.test.tg');
    expect(config('services.cashpay.username'))->toBe('user.demo');
    expect(config('services.ai.default'))->toBe('gemini');
    expect(config('services.anthropic.key'))->toBe('sk-ant-abc');
    expect(config('services.grok.key'))->toBe('xai-abc');
    expect(config('services.grok.model'))->toBe('grok-4');
});

it('enregistre les identifiants LiveKit depuis l’admin et les applique', function () {
    $staff = adminStaff();

    Livewire::actingAs($staff, 'web')
        ->test(Index::class)
        ->set('meeting_default', 'livekit')
        ->set('livekit_url', 'wss://ukara.livekit.cloud')
        ->set('livekit_api_key', 'APIkey')
        ->set('livekit_api_secret', 'secret-value-1234567890')
        ->call('save')
        ->assertHasNoErrors();

    expect(Settings::get('livekit.url'))->toBe('wss://ukara.livekit.cloud');
    expect(Settings::get('livekit.api_secret'))->toBe('secret-value-1234567890');

    // Appliqué à la config au boot → LiveKit devient le fournisseur disponible.
    $provider = new AppServiceProvider(app());
    (fn () => $this->applySettingsToConfig())->call($provider);

    expect(config('meetings.default'))->toBe('livekit');
    expect(config('meetings.livekit.url'))->toBe('wss://ukara.livekit.cloud');
    expect((new LiveKitProvider)->isAvailable())->toBeTrue();
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
