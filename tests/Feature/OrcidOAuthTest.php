<?php

use App\Support\OrcidClient;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;

// verifiedAccount() est défini dans tests/Pest.php.

function configureOrcid(): void
{
    Settings::set('orcid.environment', 'sandbox');
    Settings::set('orcid.client_id', 'APP-TEST');
    Settings::set('orcid.client_secret', 'secret');
}

it('construit une URL d’autorisation avec le scope /authenticate', function () {
    configureOrcid();
    $url = (new OrcidClient)->authorizeUrl('https://gesco.test/callback', 'state123');

    expect($url)->toStartWith('https://sandbox.orcid.org/oauth/authorize?')
        ->and($url)->toContain('client_id=APP-TEST')
        ->and($url)->toContain('scope=%2Fauthenticate')
        ->and($url)->toContain('state=state123');
});

it('échange le code contre l’iD ORCID authentifié', function () {
    configureOrcid();
    Http::fake([
        '*orcid.org/oauth/token' => Http::response([
            'orcid' => '0000-0002-1825-0097', 'name' => 'Awa Kodjo',
            'access_token' => 'tok', 'token_type' => 'bearer',
        ], 200),
    ]);

    $data = (new OrcidClient)->exchange('the-code', 'https://gesco.test/callback');

    expect($data['orcid'])->toBe('0000-0002-1825-0097')
        ->and($data['name'])->toBe('Awa Kodjo');
});

it('redirige vers ORCID au démarrage de la connexion', function () {
    configureOrcid();
    $account = verifiedAccount();

    $res = $this->actingAs($account, 'account')->get(route('account.orcid.connect'));

    $res->assertRedirectContains('sandbox.orcid.org/oauth/authorize');
    $res->assertSessionHas('orcid_oauth_state');
});

it('enregistre et marque vérifié l’ORCID au retour', function () {
    configureOrcid();
    $account = verifiedAccount();
    Http::fake([
        '*orcid.org/oauth/token' => Http::response([
            'orcid' => '0000-0002-1825-0097', 'name' => 'Awa Kodjo',
        ], 200),
    ]);

    $this->actingAs($account, 'account')
        ->withSession(['orcid_oauth_state' => 'st'])
        ->get(route('account.orcid.callback', ['code' => 'abc', 'state' => 'st']))
        ->assertRedirect(route('account.profile'));

    $fresh = $account->fresh();
    expect($fresh->orcid)->toBe('0000-0002-1825-0097')
        ->and($fresh->orcidVerified())->toBeTrue();
});

it('rejette un retour dont le state ne correspond pas (anti-CSRF)', function () {
    configureOrcid();
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->withSession(['orcid_oauth_state' => 'st'])
        ->get(route('account.orcid.callback', ['code' => 'abc', 'state' => 'WRONG']))
        ->assertRedirect(route('account.profile'));

    expect($account->fresh()->orcid)->toBeNull();
});

it('refuse la connexion si ORCID n’est pas configuré', function () {
    $account = verifiedAccount();

    $this->actingAs($account, 'account')
        ->get(route('account.orcid.connect'))
        ->assertRedirect(route('account.profile'))
        ->assertSessionHas('ukc_error');
});
