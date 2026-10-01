<?php

use App\Support\Branding;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logos de la plateforme, remplaçables depuis Réglages › Général.
 *
 * **Deux** fichiers seulement : large et compact. Le rendu sur fond sombre est
 * calculé (blanchiment CSS), pas téléversé.
 *
 * ⚠️ Repli systématique sur les fichiers livrés : une installation neuve, ou un
 * réglage pointant vers un fichier disparu, doit donner une image affichable -
 * jamais une image cassée.
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

it('ne blanchit pas les logos livrés', function () {
    // logo-uk-white.png est déjà blanc, et blanchir le monogramme livré en
    // ferait un carré plein : le filtre ne vaut que pour un fichier téléversé.
    expect(Branding::whitensOnDark(Branding::WIDE))->toBeFalse();
    expect(Branding::whitensOnDark(Branding::MARK))->toBeFalse();
});

it('retombe sur le logo livré si le fichier réglé a disparu', function () {
    Storage::fake('public');
    Settings::set(Branding::WIDE, 'branding/supprime.png');
    Settings::set(Branding::MARK, 'branding/supprime-aussi.png');

    expect(Branding::wide())->toContain('img/logo-uk.png');
    expect(Branding::wideWhite())->toContain('img/logo-uk-white.png');
    expect(Branding::mark())->toContain('img/apple-touch-icon.png');
    expect(Branding::isCustom())->toBeFalse();
    expect(Branding::whitensOnDark(Branding::WIDE))->toBeFalse();
});

it('téléverse les deux logos depuis les réglages', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), logoBase() + [
            'app_logo' => UploadedFile::fake()->image('large.png', 320, 120),
            'app_logo_mark' => UploadedFile::fake()->image('mono.png', 120, 120),
        ])
        ->assertRedirect();

    foreach (Branding::KEYS as $key) {
        $path = Settings::get($key);
        expect($path)->not->toBeEmpty();
        Storage::disk('public')->assertExists($path);
        expect(Branding::url($key))->toContain($path);
    }

    expect(Branding::wide())->toContain(Settings::get(Branding::WIDE));
    expect(Branding::mark())->toContain(Settings::get(Branding::MARK));
    expect(Branding::isCustom())->toBeTrue();
});

it('sert le MÊME fichier sur fond sombre, à blanchir en CSS', function () {
    Storage::fake('public');

    // ⚠️ Le cœur de la fonctionnalité : on ne demande pas une version claire à
    // l'admin, on signale qu'il faut inverser celle qu'il a donnée.
    $this->actingAs(adminStaff(), 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo' => UploadedFile::fake()->image('large.png', 320, 120),
    ]);

    expect(Branding::wideWhite())->toBe(Branding::wide());
    expect(Branding::whitensOnDark(Branding::WIDE))->toBeTrue();

    // Le monogramme n'a pas été touché : il reste livré, donc sans filtre.
    expect(Branding::whitensOnDark(Branding::MARK))->toBeFalse();
});

it('pose la classe de blanchiment sur les surfaces sombres', function () {
    Storage::fake('public');
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo' => UploadedFile::fake()->image('large.png', 320, 120),
    ]);

    auth('web')->logout();
    $this->get(route('account.login'))->assertOk()->assertSee('logo-auto-white', false);
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

it('revient au logo livré logo par logo', function () {
    Storage::fake('public');
    $staff = adminStaff();

    $this->actingAs($staff, 'web')->post(route('admin.settings.update'), logoBase() + [
        'app_logo' => UploadedFile::fake()->image('large.png'),
        'app_logo_mark' => UploadedFile::fake()->image('mono.png'),
    ]);
    $wide = Settings::get(Branding::WIDE);
    $mark = Settings::get(Branding::MARK);

    // On ne remet à zéro que le logo large : le compact reste en place.
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
        'app_logo' => UploadedFile::fake()->image('large.png', 320, 120),
        'app_logo_mark' => UploadedFile::fake()->image('mono.png', 120, 120),
    ]);

    $wide = Settings::get(Branding::WIDE);
    $mark = Settings::get(Branding::MARK);

    // Menu latéral : le monogramme, qui tient aussi quand la barre est réduite.
    $this->actingAs($staff, 'web')->get(route('admin.dashboard'))
        ->assertOk()->assertSee($mark, false)->assertDontSee($wide, false);

    // La page de connexion est réservée aux visiteurs : on se déconnecte d'abord.
    auth('web')->logout();

    // Fond sombre et place disponible : le logo large.
    $this->get(route('account.login'))->assertOk()->assertSee($wide, false);
});
