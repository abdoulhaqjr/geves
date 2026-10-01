<?php

use App\Support\Branding;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logo de la plateforme, remplaçable depuis Réglages › Général.
 *
 * ⚠️ Repli systématique sur le fichier livré : une installation neuve, ou un
 * réglage pointant vers un fichier disparu, doit afficher le logo d'origine -
 * jamais une image cassée.
 */

// adminStaff() -> tests/Pest.php

it('sert le logo livré tant qu’aucun n’est téléversé', function () {
    expect(Branding::logo())->toContain('img/logo-uk.png');
    expect(Branding::logoWhite())->toContain('img/logo-uk-white.png');
    expect(Branding::isCustom())->toBeFalse();
});

it('retombe sur le logo livré si le fichier réglé a disparu', function () {
    Storage::fake('public');
    Settings::set(Branding::KEY, 'branding/supprime.png');

    expect(Branding::logo())->toContain('img/logo-uk.png');
    expect(Branding::isCustom())->toBeFalse();
});

it('téléverse le logo depuis les réglages', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), [
            'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
            'meeting_default' => 'livekit', 'sms_default' => 'log',
            'app_logo' => UploadedFile::fake()->image('logo.png', 320, 120),
        ])
        ->assertRedirect();

    $path = Settings::get(Branding::KEY);
    expect($path)->not->toBeEmpty();
    Storage::disk('public')->assertExists($path);
    expect(Branding::logo())->toContain($path);
    expect(Branding::isCustom())->toBeTrue();
});

it('remplace le fichier précédent au lieu de l’empiler', function () {
    Storage::fake('public');
    $staff = adminStaff();
    $base = [
        'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
        'meeting_default' => 'livekit', 'sms_default' => 'log',
    ];

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), $base + [
        'app_logo' => UploadedFile::fake()->image('premier.png'),
    ]);
    $first = Settings::get(Branding::KEY);

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), $base + [
        'app_logo' => UploadedFile::fake()->image('second.png'),
    ]);
    $second = Settings::get(Branding::KEY);

    // ⚠️ Un logo n'a pas d'historique : l'ancien ne doit plus être servi.
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('revient au logo livré quand on le demande', function () {
    Storage::fake('public');
    $staff = adminStaff();
    $base = [
        'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
        'meeting_default' => 'livekit', 'sms_default' => 'log',
    ];

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), $base + [
        'app_logo' => UploadedFile::fake()->image('logo.png'),
    ]);
    $path = Settings::get(Branding::KEY);

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), $base + ['app_logo_remove' => '1']);

    Storage::disk('public')->assertMissing($path);
    expect(Branding::logo())->toContain('img/logo-uk.png');
});

it('refuse un fichier qui n’est pas une image', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), [
            'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
            'meeting_default' => 'livekit', 'sms_default' => 'log',
            'app_logo' => UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf'),
        ])
        ->assertSessionHasErrors('app_logo');

    expect(Settings::get(Branding::KEY))->toBeEmpty();
});

it('affiche le logo partout, du back-office à la page de connexion', function () {
    Storage::fake('public');
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), [
        'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
        'meeting_default' => 'livekit', 'sms_default' => 'log',
        'app_logo' => UploadedFile::fake()->image('logo.png'),
        'app_logo_white' => UploadedFile::fake()->image('logo-blanc.png'),
    ]);

    $logo = Settings::get(Branding::KEY);
    $white = Settings::get(Branding::KEY_WHITE);

    $this->actingAs($staff, 'web')->get(route('admin.dashboard'))->assertOk()->assertSee($logo, false);

    // La page de connexion est réservée aux visiteurs : on se déconnecte d'abord.
    auth('web')->logout();
    $this->get(route('account.login'))->assertOk()->assertSee($white, false);
});
