<?php

use App\Support\IpCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Le pays d'une adresse IP
|--------------------------------------------------------------------------
|
| Trois choses se vérifient ici, et elles échouaient toutes les trois en
| silence : l'adresse enregistrée derrière un mandataire, la base locale
| absente sans que rien ne le dise, et le repli hébergé qui ne doit partir chez
| un tiers que si on l'a décidé.
|
*/

beforeEach(function () {
    Cache::flush();
    config(['geoip.database_path' => storage_path('app/geoip/introuvable-'.uniqid().'.mmdb')]);
});

/** Une page minimale qui rend l'adresse telle que le socle la résout. */
function routeDeSonde(): string
{
    Route::get('/_sonde-ip', fn (Request $r) => $r->ip());

    return '/_sonde-ip';
}

it('rend l’adresse du visiteur quand un mandataire est déclaré', function () {
    config(['trustedproxy.proxies' => '*']);

    $this->get(routeDeSonde(), ['X-Forwarded-For' => '41.207.185.10'])
        ->assertOk()
        ->assertSee('41.207.185.10');
});

it('déclare les mandataires sous la clé que le socle relit', function () {
    // ⚠️ Le nom du fichier et celui de la clé ne se choisissent pas :
    // `TrustProxies` lit `config('trustedproxy.proxies')` à chaque requête
    // quand rien ne lui a été fixé. Renommer l'un ou l'autre couperait le
    // branchement SANS RIEN CASSER - on ne s'en apercevrait qu'en production,
    // à la colonne « Pays ».
    expect(require config_path('trustedproxy.php'))->toHaveKey('proxies');
});

it('ignore l’en-tête de transfert quand aucun mandataire n’est déclaré', function () {
    // ⚠️ C'est la moitié qui protège : servi en direct, un visiteur choisirait
    // l'adresse que GEVES enregistre sur sa connexion.
    config(['trustedproxy.proxies' => null]);

    $this->get(routeDeSonde(), ['X-Forwarded-For' => '41.207.185.10'])
        ->assertOk()
        ->assertDontSee('41.207.185.10');
});

it('ne cherche jamais le pays d’une adresse privée', function () {
    config(['geoip.fallback.enabled' => true]);
    Http::preventStrayRequests();

    expect(IpCountry::isPublic('127.0.0.1'))->toBeFalse()
        ->and(IpCountry::isPublic('192.168.1.10'))->toBeFalse()
        ->and(IpCountry::isPublic('41.207.185.10'))->toBeTrue()
        ->and((new IpCountry)->namesFor(['127.0.0.1', '192.168.1.10', '10.0.0.4']))->toBe([]);
});

it('garde le repli hébergé ÉTEINT par défaut', function () {
    // Allumer ce repli envoie l'adresse des participants chez un tiers : c'est
    // une décision, pas un défaut qu'on subit.
    expect(config('geoip.fallback.enabled'))->toBeFalse()
        ->and(IpCountry::fallbackEnabled())->toBeFalse();

    Http::preventStrayRequests();
    expect((new IpCountry)->namesFor(['41.207.185.10']))->toBe([]);
});

it('n’annonce rien quand il n’y a aucun moyen de résoudre un pays', function () {
    config(['geoip.fallback.enabled' => false]);

    expect(IpCountry::databaseInstalled())->toBeFalse()
        ->and(IpCountry::resolvable())->toBeFalse();
});

it('consulte le repli hébergé une fois allumé, et retient sa réponse', function () {
    config(['geoip.fallback.enabled' => true]);
    Http::fake(['ipapi.co/*' => Http::response(['country_name' => 'Togo', 'country' => 'TG'])]);

    expect((new IpCountry)->namesFor(['41.207.185.10']))->toBe(['41.207.185.10' => 'Togo']);
    Http::assertSentCount(1);

    // Deuxième affichage de la même liste : rien ne repart chez le tiers.
    expect((new IpCountry)->namesFor(['41.207.185.10']))->toBe(['41.207.185.10' => 'Togo']);
    Http::assertSentCount(1);
});

it('plafonne le nombre d’appels sortants par affichage', function () {
    // Une liste de vingt comptes ne doit pas devenir vingt appels sortants au
    // rendu d'une seule page.
    config(['geoip.fallback.enabled' => true, 'geoip.fallback.max_per_request' => 3]);
    Http::fake(['ipapi.co/*' => Http::response(['country_name' => 'Togo'])]);

    $ips = ['41.207.185.1', '41.207.185.2', '41.207.185.3', '41.207.185.4', '41.207.185.5'];
    (new IpCountry)->namesFor($ips);

    Http::assertSentCount(3);
});

it('ne retient pas une erreur du service comme un pays', function () {
    config(['geoip.fallback.enabled' => true]);
    Http::fake(['ipapi.co/*' => Http::response(['error' => true, 'reason' => 'RateLimited'])]);

    expect((new IpCountry)->namesFor(['41.207.185.10']))->toBe([]);
});

it('dit à l’écran que la base de géolocalisation manque', function () {
    $admin = usersAdmin();

    $this->actingAs($admin, 'web')->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('php artisan geoip:update');
});

it('ne crie pas au manque quand la base est là', function () {
    $chemin = storage_path('app/geoip/sonde-'.uniqid().'.mmdb');
    @mkdir(dirname($chemin), 0755, true);
    file_put_contents($chemin, 'base factice');
    config(['geoip.database_path' => $chemin]);

    $admin = usersAdmin();

    try {
        $this->actingAs($admin, 'web')->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('php artisan geoip:update');
    } finally {
        @unlink($chemin);
    }
});

it('distingue une adresse privée d’un pays inconnu', function () {
    $admin = usersAdmin();
    $admin->forceFill(['last_login_ip' => '127.0.0.1', 'last_login_at' => now()])->save();

    $this->actingAs($admin, 'web')->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Réseau local');
});
