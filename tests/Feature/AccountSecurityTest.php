<?php

use App\Models\Account;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;

/** Compte de test avec mot de passe connu. */
function securityAccount(string $password = 'MotDePasse123!'): Account
{
    $account = Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => 'awa@example.tg', 'password' => $password,
    ]);
    $account->markEmailAsVerified();

    return $account;
}

/** Active la 2FA sur un compte avec un secret et des codes connus. */
function enableTotp(Account $account, string $secret, array $recovery = ['ABCDE-12345']): Account
{
    $account->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => $recovery,
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $account->fresh();
}

/** Code TOTP valide pour l'instant courant. */
function currentOtp(string $secret): string
{
    return Totp::codeAt($secret, (int) floor(time() / 30));
}

// --- Mot de passe ---------------------------------------------------------

it('change le mot de passe avec le mot de passe actuel correct', function () {
    $account = securityAccount('AncienPass123!');

    $this->actingAs($account, 'account')->post(route('account.security.password'), [
        'current_password' => 'AncienPass123!',
        'password' => 'NouveauPass456!',
        'password_confirmation' => 'NouveauPass456!',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('NouveauPass456!', $account->fresh()->password))->toBeTrue();
});

it('refuse le changement de mot de passe si le mot de passe actuel est faux', function () {
    $account = securityAccount('AncienPass123!');

    $this->actingAs($account, 'account')->post(route('account.security.password'), [
        'current_password' => 'faux',
        'password' => 'NouveauPass456!',
        'password_confirmation' => 'NouveauPass456!',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('AncienPass123!', $account->fresh()->password))->toBeTrue();
});

// --- Activation 2FA -------------------------------------------------------

it('active la 2FA après confirmation d’un code TOTP valide', function () {
    $account = securityAccount();

    $this->actingAs($account, 'account')->post(route('account.security.totp.start'))->assertRedirect();

    $secret = session('account.2fa.pending_secret');
    expect($secret)->not->toBeEmpty();

    $this->actingAs($account, 'account')
        ->post(route('account.security.totp.confirm'), ['code' => currentOtp($secret)])
        ->assertSessionHasNoErrors();

    $fresh = $account->fresh();
    expect($fresh->hasTotpEnabled())->toBeTrue();
    expect($fresh->countRecoveryCodes())->toBe(8);
});

it('rejette un code TOTP invalide à l’activation', function () {
    $account = securityAccount();

    $this->actingAs($account, 'account')->post(route('account.security.totp.start'));
    $this->actingAs($account, 'account')
        ->post(route('account.security.totp.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($account->fresh()->hasTotpEnabled())->toBeFalse();
});

// --- Désactivation / codes ------------------------------------------------

it('désactive la 2FA avec le bon mot de passe', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret);

    $this->actingAs($account, 'account')
        ->post(route('account.security.disable'), ['disable_password' => 'Pass123!'])
        ->assertSessionHasNoErrors();

    expect($account->fresh()->hasTotpEnabled())->toBeFalse();
});

it('refuse la désactivation avec un mauvais mot de passe', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret);

    $this->actingAs($account, 'account')
        ->post(route('account.security.disable'), ['disable_password' => 'faux'])
        ->assertSessionHasErrors('disable_password');

    expect($account->fresh()->hasTotpEnabled())->toBeTrue();
});

it('régénère les codes de récupération avec le bon mot de passe', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret, ['OLD01-OLD02']);

    $this->actingAs($account, 'account')
        ->post(route('account.security.recovery'), ['disable_password' => 'Pass123!'])
        ->assertSessionHasNoErrors();

    $fresh = $account->fresh();
    expect($fresh->countRecoveryCodes())->toBe(8);
    expect($fresh->consumeRecoveryCode('OLD01-OLD02'))->toBeFalse(); // ancien code invalidé
});

// --- Challenge à la connexion ---------------------------------------------

it('exige un second facteur à la connexion quand la 2FA est active', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret);

    $this->post(route('account.login.store'), [
        'email' => 'awa@example.tg',
        'password' => 'Pass123!',
    ])->assertRedirect(route('account.login')); // renvoi vers l'écran de second facteur

    expect(auth('account')->check())->toBeFalse(); // pas encore connecté
});

it('finalise la connexion avec un code TOTP valide', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret);

    $this->post(route('account.login.store'), [
        'email' => 'awa@example.tg',
        'password' => 'Pass123!',
    ])->assertRedirect(route('account.login'));

    $this->post(route('account.login.confirm'), ['code' => currentOtp($secret)])
        ->assertRedirect(route('account.dashboard'));

    expect(auth('account')->check())->toBeTrue();
});

it('accepte un code de récupération à usage unique à la connexion', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret, ['ABCDE-12345']);

    $this->post(route('account.login.store'), [
        'email' => 'awa@example.tg',
        'password' => 'Pass123!',
    ])->assertRedirect(route('account.login'));

    $this->post(route('account.login.confirm'), ['code' => 'ABCDE-12345'])
        ->assertRedirect(route('account.dashboard'));

    expect(auth('account')->check())->toBeTrue();
    expect($account->fresh()->countRecoveryCodes())->toBe(0); // code consommé
});

it('rejette un second facteur invalide', function () {
    $secret = Totp::generateSecret();
    $account = enableTotp(securityAccount('Pass123!'), $secret);

    $this->post(route('account.login.store'), [
        'email' => 'awa@example.tg',
        'password' => 'Pass123!',
    ])->assertRedirect(route('account.login'));

    $this->post(route('account.login.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect(auth('account')->check())->toBeFalse();
});
