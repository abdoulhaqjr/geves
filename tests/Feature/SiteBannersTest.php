<?php

use App\Http\Controllers\Admin\SettingController;
use App\Support\Settings;
use App\Support\SiteBanners;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// adminStaff(), openEdition() -> tests/Pest.php

/**
 * **Bandeaux du site, téléversés depuis Réglages › Bannières.**
 *
 * Deux images : celle de la page d'accueil, et le **repli** des pages
 * d'événement. ⚠️ Le repli est la seule des deux qui ait une subtilité : il ne
 * doit jamais passer devant le bandeau qu'un événement a choisi lui-même.
 */

/** Les réglages obligatoires de la page, pour ne poster que la bannière étudiée. */
function bannerBase(): array
{
    return [
        'payments_default' => 'manual', 'fedapay_mode' => 'test', 'ai_default' => 'claude',
        'meeting_default' => 'livekit', 'sms_default' => 'log',
    ];
}

it('n’a aucun bandeau tant que rien n’est téléversé', function () {
    expect(SiteBanners::home())->toBeNull();
    expect(SiteBanners::eventDefault())->toBeNull();

    // La page d'accueil reste sur le dégradé de la charte : pas de modificateur,
    // pas de variable d'image.
    $this->get(route('home'))->assertOk()
        ->assertDontSee('hero--image', false)
        ->assertDontSee('--hero-bg', false);
});

it('ouvre l’onglet Bannières des réglages', function () {
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.settings', ['tab' => 'banners']))
        ->assertOk()
        ->assertSee('Bannières')
        ->assertSee('Bandeau de la page d’accueil', false)
        ->assertSee('Bandeau par défaut des pages', false)
        ->assertSee('name="'.SiteBanners::HOME.'"', false)
        ->assertSee('name="'.SiteBanners::EVENT_DEFAULT.'"', false);

    expect(SettingController::TABS)->toContain('banners');
});

it('téléverse le bandeau d’accueil et la page d’accueil le montre', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), bannerBase() + [
            SiteBanners::HOME => UploadedFile::fake()->image('accueil.jpg', 1920, 820),
        ])
        ->assertRedirect()
        // ⚠️ On revient sur l'onglet des bannières : renvoyer vers « Général »
        // priverait l'admin de l'aperçu de ce qu'il vient de poser.
        ->assertSessionHas('settings_tab', 'banners');

    $path = (string) Settings::get(SiteBanners::HOME);
    expect($path)->toStartWith(SiteBanners::DIR.'/');
    Storage::disk('public')->assertExists($path);

    $url = SiteBanners::home();
    expect($url)->not->toBeNull();

    $this->get(route('home'))->assertOk()
        ->assertSee('hero--image', false)
        ->assertSee('--hero-bg: url(', false)
        ->assertSee($url, false);
});

it('habille d’un bandeau par défaut l’événement qui n’a pas le sien', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), bannerBase() + [
            SiteBanners::EVENT_DEFAULT => UploadedFile::fake()->image('defaut.jpg', 1920, 820),
        ])->assertRedirect();

    $defaut = SiteBanners::eventDefault();
    expect($defaut)->not->toBeNull();

    $event = openEdition();
    expect($event->settings['hero_image'] ?? null)->toBeNull();

    $this->get(route('public.colloque', $event->slug))->assertOk()->assertSee($defaut, false);
});

it('ne passe JAMAIS devant le bandeau choisi par un événement', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), bannerBase() + [
            SiteBanners::EVENT_DEFAULT => UploadedFile::fake()->image('defaut.jpg', 1920, 820),
        ])->assertRedirect();

    // ⚠️ Le travers à éviter : un repli posé au niveau du site qui écraserait
    // le visuel qu'une organisation a choisi pour SON colloque.
    $event = openEdition(['hero_image' => 'https://exemple.tg/affiche-du-colloque.jpg']);

    $this->get(route('public.colloque', $event->slug))->assertOk()
        ->assertSee('https://exemple.tg/affiche-du-colloque.jpg', false)
        ->assertDontSee((string) SiteBanners::eventDefault(), false);
});

it('remplace le fichier précédent au lieu de l’empiler', function () {
    Storage::fake('public');
    $admin = adminStaff();

    $this->actingAs($admin, 'web')->post(route('admin.settings.update'), bannerBase() + [
        SiteBanners::HOME => UploadedFile::fake()->image('un.jpg', 1920, 820),
    ])->assertRedirect();
    $premier = (string) Settings::get(SiteBanners::HOME);

    $this->actingAs($admin, 'web')->post(route('admin.settings.update'), bannerBase() + [
        SiteBanners::HOME => UploadedFile::fake()->image('deux.jpg', 1920, 820),
    ])->assertRedirect();
    $second = (string) Settings::get(SiteBanners::HOME);

    expect($second)->not->toBe($premier);
    // ⚠️ Un bandeau n'a pas d'historique : l'ancien resterait servi publiquement.
    Storage::disk('public')->assertMissing($premier);
    Storage::disk('public')->assertExists($second);
});

it('supprime le bandeau et rend la main au dégradé', function () {
    Storage::fake('public');
    $admin = adminStaff();

    $this->actingAs($admin, 'web')->post(route('admin.settings.update'), bannerBase() + [
        SiteBanners::HOME => UploadedFile::fake()->image('accueil.jpg', 1920, 820),
    ])->assertRedirect();
    $path = (string) Settings::get(SiteBanners::HOME);

    $this->actingAs($admin, 'web')->post(route('admin.settings.update'), bannerBase() + [
        SiteBanners::HOME.'_remove' => '1',
    ])->assertRedirect()->assertSessionHas('settings_tab', 'banners');

    // `Settings::get` rend le defaut (null) pour une valeur vide : on verifie
    // donc l'absence de valeur, pas la chaine vide qu'on vient d'ecrire.
    expect(Settings::get(SiteBanners::HOME))->toBeEmpty();
    expect(SiteBanners::home())->toBeNull();
    Storage::disk('public')->assertMissing($path);

    $this->get(route('home'))->assertOk()->assertDontSee('hero--image', false);
});

it('ne sert jamais une image cassée quand le fichier a disparu', function () {
    Storage::fake('public');

    // ⚠️ Même garde que Branding : un réglage qui survit à la suppression de son
    // fichier doit rendre null, pas une URL morte.
    Settings::set(SiteBanners::HOME, SiteBanners::DIR.'/envole.jpg');
    Settings::set(SiteBanners::EVENT_DEFAULT, SiteBanners::DIR.'/envole-aussi.jpg');

    expect(SiteBanners::home())->toBeNull();
    expect(SiteBanners::eventDefault())->toBeNull();

    $this->get(route('home'))->assertOk()->assertDontSee('hero--image', false);
});

it('refuse un fichier qui n’est pas une image et rouvre le bon onglet', function () {
    Storage::fake('public');

    $this->actingAs(adminStaff(), 'web')
        ->post(route('admin.settings.update'), bannerBase() + [
            SiteBanners::HOME => UploadedFile::fake()->create('barème.pdf', 40, 'application/pdf'),
        ])
        ->assertSessionHasErrors(SiteBanners::HOME)
        // L'erreur doit ramener sur l'onglet fautif, sinon le message s'affiche
        // sous un panneau masqué.
        ->assertSessionHas('settings_tab', 'banners');

    expect(Settings::get(SiteBanners::HOME))->toBeEmpty();
});

/**
 * ⚠️ **Le voile posé sur un bandeau est bâti sur `--ukc-hero-navy` (#14205a).**
 * Il portait #0f1a33, la teinte quasi noire que la charte écarte, écrite EN
 * LIGNE dans la vue - donc hors de portée de `DegradeHerosTest`, qui relit les
 * feuilles de style. Tant que seuls de rares événements téléversaient un
 * visuel, ce voile se voyait peu ; avec la bannière par défaut, c'est celui de
 * tous les événements.
 */
it('voile les bandeaux avec le navy de la charte, jamais le quasi-noir', function () {
    // ⚠️ On RELÈVE les fautifs au lieu d'enchaîner les `not->toContain()` : le
    // second argument de `toContain()` n'est pas un message, c'est une
    // SECONDE AIGUILLE. Nié, « ne contient pas (A et B) » passe dès que B
    // manque - et B était ma phrase d'explication, absente par construction.
    // Le cas ne mordait donc jamais ; un sabotage l'a montré.
    // ⚠️ La règle vise les DÉGRADÉS, pas la teinte : la charte autorise
    // #0f1a33 en aplat, et `public.css` s'en sert pour des ombres portées, la
    // pastille d'une vignette et le voile d'une modale. Une première version
    // les dénonçait tous les sept - une garde qui crie sur du code correct
    // finit par être désactivée.
    $fautifs = [];

    foreach (['resources/views/public/colloque.blade.php', 'resources/css/public.css'] as $relatif) {
        $src = (string) file_get_contents(base_path($relatif));
        $src = (string) preg_replace('#/\*.*?\*/#s', '', $src);
        $src = (string) preg_replace('#\{\{--.*?--\}\}#s', '', $src);

        foreach (explode("\n", $src) as $i => $ligne) {
            if (str_contains($ligne, 'linear-gradient') && preg_match('/rgba\(\s*15\s*,\s*26\s*,\s*51/', $ligne)) {
                $fautifs[] = $relatif.':'.($i + 1).'  dégradé de voile en #0f1a33';
            }
        }
    }
    sort($fautifs);

    expect($fautifs)->toBe([]);
});
