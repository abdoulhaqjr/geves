<?php

use App\Providers\AppServiceProvider;
use App\Support\CookieConsent;
use App\Support\Settings;
use Illuminate\Support\Facades\Config;

// adminStaff() -> AdminSubmissionWorkflowTest

it('affiche le bandeau sur le public, et nulle part ailleurs', function () {
    Config::set('cookies.notice_enabled', true);

    $this->get('/')->assertOk()->assertSee('ukcCookieNotice', false);

    // ⚠️ La page qui EXPLIQUE les cookies ne porte pas le bandeau : elle dit déjà
    // tout ce qu'il annonce, et il masquerait le bas de son propre inventaire.
    $this->get(route('public.cookies'))->assertOk()->assertDontSee('ukcCookieNotice', false);

    // Le back-office est un espace de travail ouvert avec un compte.
    $this->actingAs(adminStaff(), 'web')->get('/admin')
        ->assertOk()->assertDontSee('ukcCookieNotice', false);
});

it('le réglage en base éteint le bandeau', function () {
    Settings::set('cookies.notice_enabled', false);
    app(AppServiceProvider::class, ['app' => app()])->boot();

    expect(config('cookies.notice_enabled'))->toBeFalse();

    $this->get('/')->assertOk()->assertDontSee('ukcCookieNotice', false);
});

it('⚠️ rend le MÊME balisage que le cookie soit posé ou non', function () {
    // Les pages publiques sont faites pour être cacheables : si le serveur
    // rendait le bandeau « seulement quand le cookie manque », l'état d'un
    // visiteur entrerait dans le cache de tous. C'est le navigateur qui décide.
    Config::set('cookies.notice_enabled', true);

    $sans = $this->get('/')->assertOk()->getContent();
    $avec = $this->withCookie(CookieConsent::cookieName(), '1')->get('/')->assertOk()->getContent();

    expect($avec)->toContain('ukcCookieNotice')
        ->and($sans)->toContain('ukcCookieNotice');
});

it('le bandeau part MASQUÉ : sinon il clignote pour qui l’a déjà lu', function () {
    Config::set('cookies.notice_enabled', true);

    $this->get('/')->assertOk()
        ->assertSee('id="ukcCookieNotice" hidden', false);
});

it('la page liste les cookies RÉELLEMENT posés, lus dans la configuration', function () {
    Config::set('session.cookie', 'geves_session_essai');
    Config::set('services.turnstile.site_key', '');
    Config::set('services.turnstile.secret_key', '');

    $this->get(route('public.cookies'))->assertOk()
        ->assertSee('geves_session_essai')
        ->assertSee('XSRF-TOKEN')
        ->assertSee(CookieConsent::cookieName())
        // ⚠️ Sans ses clés, Turnstile ne se charge sur aucune page : l'annoncer
        // serait annoncer un dépôt qui n'arrive pas.
        ->assertDontSee('Cloudflare Turnstile');
});

it('annonce Turnstile dès que ses deux clés sont là', function () {
    Config::set('services.turnstile.site_key', 'site');
    Config::set('services.turnstile.secret_key', 'secret');

    $this->get(route('public.cookies'))->assertOk()->assertSee('Cloudflare Turnstile');

    expect(collect(CookieConsent::inventory())->pluck('name'))->toContain('Cloudflare Turnstile');
});

it('n’annonce AUCUN cookie de mesure : il n’y en a pas', function () {
    // Garde de fond : le jour où un outil de mesure est branché, ce cas tombe et
    // oblige à revoir le bandeau - qui promet aujourd'hui le contraire.
    $noms = collect(CookieConsent::inventory())->pluck('name')->implode(' ');

    expect($noms)->not->toContain('_ga')
        ->and($noms)->not->toContain('_gid')
        ->and($noms)->not->toContain('_fbp');

    $sources = file_get_contents(base_path('app/Http/Middleware/ContentSecurityPolicy.php'));
    foreach (['googletagmanager', 'google-analytics', 'matomo', 'hotjar'] as $mouchard) {
        expect($sources)->not->toContain($mouchard);
    }
});

it('la page est atteignable depuis le PIED DE PAGE, pas seulement par le bandeau', function () {
    // ⚠️ Sans ce lien, la page n'existerait que pour qui a cliqué sur le bandeau,
    // donc une seule fois - et plus jamais après.
    // ⚠️⚠️ ON REGARDE DANS LE PIED DE PAGE, pas dans la page entière : le bandeau
    // porte la MÊME adresse (« En savoir plus »), donc une recherche globale reste
    // verte même si le lien du pied disparaît. Trouvé par sabotage, pas à la
    // relecture - le cas passait au vert avec le lien retiré.
    // ⚠️ Le pied se borne des DEUX côtés : le bandeau est rendu par la mise en
    // page APRÈS `</footer>`, donc couper à partir de `<footer` l'embarque aussi -
    // et le cas repassait au vert, lien du pied retiré. Deuxième sabotage pour
    // s'en apercevoir.
    $html = $this->get('/')->assertOk()->getContent();
    $debut = strpos($html, '<footer class="site-footer"');
    $fin = strpos($html, '</footer>', (int) $debut);

    expect($debut)->not->toBeFalse()
        ->and($fin)->not->toBeFalse();

    $pied = substr($html, (int) $debut, (int) $fin - (int) $debut);

    expect($pied)->toContain(route('public.cookies'));
});

it('le bandeau réserve la place de la bulle de l’assistante', function () {
    // Les deux sont fixés en bas : sans dégagement ils se recouvrent, et au doigt
    // il n'y a pas de pointeur pour déclencher la politesse de la bulle. Le
    // dégagement reprend le JETON, jamais une valeur en dur.
    $css = file_get_contents(base_path('resources/css/public.css'));
    $bloc = substr($css, strpos($css, '.ukc-cookie__inner'), 400);

    expect($bloc)->toContain('var(--ukc-cb-fab)');
});

it('expose le réglage dans l’onglet Sécurité', function () {
    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.settings', ['tab' => 'security']))
        ->assertOk()
        ->assertSee('cookies_notice_enabled', false);
});
