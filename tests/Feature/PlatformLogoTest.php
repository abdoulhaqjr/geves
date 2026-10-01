<?php

use App\Support\Branding;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logos de la plateforme, remplaçables depuis Réglages › Général.
 *
 * Deux FORMES (large, monogramme) x deux FONDS (clair, sombre).
 *
 * ⚠️ Repli en cascade : une installation neuve, un réglage pointant vers un
 * fichier disparu, ou une forme téléversée sur un seul fond doivent toujours
 * donner une image affichable - jamais une image cassée.
 */

// adminStaff() -> tests/Pest.php

/**
 * Les réglages obligatoires de la page, pour ne poster que le logo étudié.
 *
 * @return array<string, string>
 */
function logoBase(): array
{
    return [
        'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
        'meeting_default' => 'livekit', 'sms_default' => 'log',
    ];
}

it('sert les logos livrés tant qu’aucun n’est téléversé', function () {
    expect(Branding::wide())->toContain('img/logo-uk.png');
    expect(Branding::wideWhite())->toContain('img/logo-uk-white.png');
    expect(Branding::mark())->toContain('img/apple-touch-icon.png');
    expect(Branding::markWhite())->toContain('img/apple-touch-icon.png');
    expect(Branding::isCustom())->toBeFalse();
});

it('retombe sur le logo livré si le fichier réglé a disparu', function () {
    Storage::fake('public');
    Settings::set(Branding::WIDE, 'branding/supprime.png');
    Settings::set(Branding::MARK, 'branding/supprime-aussi.png');

    expect(Branding::wide())->toContain('img/logo-uk.png');
    expect(Branding::mark())->toContain('img/apple-touch-icon.png');
    expect(Branding::isCustom())->toBeFalse();
});

it('téléverse les quatre variantes depuis les réglages', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), logoBase() + [
            'app_logo' => UploadedFile::fake()->image('large.png', 320, 120),
            'app_logo_white' => UploadedFile::fake()->image('large-blanc.png', 320, 120),
            'app_logo_mark' => UploadedFile::fake()->image('mono.png', 120, 120),
            'app_logo_mark_white' => UploadedFile::fake()->image('mono-blanc.png', 120, 120),
        ])
        ->assertRedirect();

    foreach (Branding::KEYS as $key) {
        $path = Settings::get($key);
        expect($path)->not->toBeEmpty();
        Storage::disk('public')->assertExists($path);
        expect(Branding::url($key))->toContain($path);
    }

    expect(Branding::wide())->toContain(Settings::get(Branding::WIDE));
    expect(Branding::wideWhite())->toContain(Settings::get(Branding::WIDE_WHITE));
    expect(Branding::mark())->toContain(Settings::get(Branding::MARK));
    expect(Branding::markWhite())->toContain(Settings::get(Branding::MARK_WHITE));
    expect(Branding::isCustom())->toBeTrue();
});

it('emprunte l’autre fond de la MÊME forme avant de retomber sur le livré', function () {
    Storage::fake('public');

    // Une seule variante de chaque forme : l'autre fond s'en sert plutôt que de
    // servir un logo d'une forme différente, qui casserait la mise en page.
    $this->actingAs(adminStaff(), 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo' => UploadedFile::fake()->image('large.png', 320, 120),
        'app_logo_mark_white' => UploadedFile::fake()->image('mono-blanc.png', 120, 120),
    ]);

    $wide = Settings::get(Branding::WIDE);
    $markWhite = Settings::get(Branding::MARK_WHITE);

    expect(Branding::wideWhite())->toContain($wide);
    expect(Branding::mark())->toContain($markWhite);
    expect(Branding::url(Branding::WIDE_WHITE))->toBeNull();
});

it('remplace le fichier précédent au lieu de l’empiler', function () {
    Storage::fake('public');
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo_mark' => UploadedFile::fake()->image('premier.png'),
    ]);
    $first = Settings::get(Branding::MARK);

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo_mark' => UploadedFile::fake()->image('second.png'),
    ]);
    $second = Settings::get(Branding::MARK);

    // ⚠️ Un logo n'a pas d'historique : l'ancien ne doit plus être servi.
    expect($second)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);
});

it('revient au logo livré variante par variante', function () {
    Storage::fake('public');
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo' => UploadedFile::fake()->image('large.png'),
        'app_logo_mark' => UploadedFile::fake()->image('mono.png'),
    ]);
    $wide = Settings::get(Branding::WIDE);
    $mark = Settings::get(Branding::MARK);

    // On ne remet à zéro que la forme large : le monogramme reste en place.
    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + ['app_logo_remove' => '1']);

    Storage::disk('public')->assertMissing($wide);
    Storage::disk('public')->assertExists($mark);
    expect(Branding::wide())->toContain('img/logo-uk.png');
    expect(Branding::mark())->toContain($mark);
});

it('refuse un fichier qui n’est pas une image', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), logoBase() + [
            'app_logo_mark' => UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf'),
        ])
        ->assertSessionHasErrors('app_logo_mark');

    expect(Settings::get(Branding::MARK))->toBeEmpty();
});

it('affiche la bonne FORME à chaque emplacement', function () {
    Storage::fake('public');
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo_white' => UploadedFile::fake()->image('large-blanc.png', 320, 120),
        'app_logo_mark' => UploadedFile::fake()->image('mono.png', 120, 120),
    ]);

    $wideWhite = Settings::get(Branding::WIDE_WHITE);
    $mark = Settings::get(Branding::MARK);

    // Menu latéral : le monogramme, qui tient aussi quand la barre est réduite.
    $this->actingAs($staff, 'web')->get(route('admin.dashboard'))
        ->assertOk()->assertSee($mark, false)->assertDontSee($wideWhite, false);

    // La page de connexion est réservée aux visiteurs : on se déconnecte d'abord.
    auth('web')->logout();

    // Fond sombre et place disponible : le logo large.
    $this->get(route('account.login'))->assertOk()->assertSee($wideWhite, false);
});
