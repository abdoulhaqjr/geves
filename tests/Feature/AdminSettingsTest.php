<?php

use App\Http\Controllers\Admin\SettingController;
use App\Meetings\Providers\LiveKitProvider;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Support\Settings;

// adminStaff() -> AdminSubmissionWorkflowTest

it('affiche les réglages au staff habilité', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->get(route('admin.settings'))
        ->assertOk()->assertSee('Général')->assertSee('SMTP');
});

it('porte chaque onglet dans son URL', function () {
    $staff = adminStaff();

    $page = $this->actingAs($staff, 'web')->get(route('admin.settings'))->assertOk();
    $page->assertSee('ukc-nav-docs', false);

    // Chaque onglet est un vrai lien : partageable, ouvrable dans un nouvel onglet.
    foreach (SettingController::TABS as $tab) {
        $page->assertSee(route('admin.settings', ['tab' => $tab]), false);
    }
});

it('ouvre l’onglet demandé par l’URL, et le premier si la clé est inconnue', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->get(route('admin.settings', ['tab' => 'meetings']))
        ->assertOk()->assertSee("ukcUrlTabs('meetings')", false);

    // ⚠️ Une clé fantaisiste n'afficherait aucun panneau : la page paraîtrait vide.
    $this->actingAs($staff, 'web')->get(route('admin.settings', ['tab' => 'nimporte']))
        ->assertOk()->assertSee("ukcUrlTabs('general')", false);
});

it('héberge les agents IA dans un onglet, avec leur propre formulaire', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->get(route('admin.settings', ['tab' => 'agents-ia']))
        ->assertOk()
        ->assertSee("ukcUrlTabs('agents-ia')", false)
        // ⚠️ Son formulaire est DISTINCT de celui des réglages : deux formulaires
        // imbriqués ne s'enregistreraient pas.
        ->assertSee(route('admin.settings.agents.update'), false)
        ->assertSee('agentsEditor(', false);
});

it('redirige l’ancienne adresse des agents IA', function () {
    // Les notifications et les signets gardent des URL absolues figées.
    $this->actingAs(adminStaff(), 'web')->get('/admin/reglages/agents-ia')
        ->assertRedirect('/admin/reglages?tab=agents-ia');
});

it('enregistre les réglages', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), [
        'from_name' => 'EventSeries UK',
        'from_address' => 'colloques@univkara.tg',
        'payments_default' => 'fedapay',
        'mail_host' => 'smtp.univkara.tg',
        'ai_default' => 'grok',
        'ai_anthropic_key' => 'sk-ant-xyz',
        'ai_anthropic_model' => 'claude-sonnet-4-5',
        'ai_grok_key' => 'xai-123',
        'ai_grok_model' => 'grok-4',
        'fedapay_mode' => 'test',
        'meeting_default' => 'livekit',
        'sms_default' => 'log',
    ])->assertSessionHasNoErrors();

    expect(Settings::get('mail.from_name'))->toBe('EventSeries UK');
    expect(Settings::get('payments.default'))->toBe('fedapay');
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
        'fedapay.webhook_secret' => 'whsec_demo',
        'fedapay.public_key' => 'pk_live_demo',
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
    expect(config('services.fedapay.webhook_secret'))->toBe('whsec_demo');
    // ⚠️ Le défaut vécu : la clé publique s'enregistrait, se réaffichait dans
    // le formulaire, et n'atteignait JAMAIS la passerelle - le formulaire
    // embarqué restait muet et le paiement repartait en redirection, sans un
    // mot. Un réglage qui ne descend pas dans la configuration ment.
    expect(config('services.fedapay.public_key'))->toBe('pk_live_demo');
    expect(config('services.ai.default'))->toBe('gemini');
    expect(config('services.anthropic.key'))->toBe('sk-ant-abc');
    expect(config('services.grok.key'))->toBe('xai-abc');
    expect(config('services.grok.model'))->toBe('grok-4');
});

it('enregistre les identifiants LiveKit depuis l’admin et les applique', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), [
        'meeting_default' => 'livekit',
        'livekit_url' => 'wss://ukara.livekit.cloud',
        'livekit_api_key' => 'APIkey',
        'livekit_api_secret' => 'secret-value-1234567890',
        'payments_default' => 'manual',
        'fedapay_mode' => 'test',
        'ai_default' => 'claude',
        'sms_default' => 'log',
    ])->assertSessionHasNoErrors();

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

    $this->actingAs($staff, 'web')
        ->post(route('admin.settings.test'), ['test_email' => 'admin@univkara.tg'])
        ->assertSessionHasNoErrors();
});

it('protège les réglages sans permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.settings'))->assertForbidden();
});
