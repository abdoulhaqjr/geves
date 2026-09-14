<?php

use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Hash;

function totpCode(string $secret): string
{
    return Totp::codeAt($secret, (int) floor(time() / 30));
}

it('challenge le staff 2FA au login au lieu de le connecter directement', function () {
    $secret = Totp::generateSecret();
    $staff = User::factory()->create([
        'email' => 'sg@uk.tg', 'password' => Hash::make('password'),
        'access_level' => User::ACCESS_SUPER_ADMIN, // → accès back-office
        'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
    ]);

    // Étape 1 : identifiants OK → challenge, pas encore connecté (guard web).
    $this->post(route('account.login.store'), ['email' => 'sg@uk.tg', 'password' => 'password'])
        ->assertRedirect(route('account.login'));
    expect(auth('web')->check())->toBeFalse();

    // Étape 2 : code TOTP valide → connecté au back-office.
    $this->post(route('account.login.confirm'), ['code' => totpCode($secret)])
        ->assertRedirect(route('admin.dashboard'));
    expect(auth('web')->check())->toBeTrue();
    expect(auth('web')->id())->toBe($staff->id);
});

it('un code 2FA invalide bloque la connexion staff', function () {
    $secret = Totp::generateSecret();
    User::factory()->create([
        'email' => 'sg2@uk.tg', 'password' => Hash::make('password'),
        'access_level' => User::ACCESS_SUPER_ADMIN,
        'two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(),
    ]);

    $this->post(route('account.login.store'), ['email' => 'sg2@uk.tg', 'password' => 'password']);
    $this->post(route('account.login.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
    expect(auth('web')->check())->toBeFalse();
});

it('active la 2FA depuis le profil et affiche les codes de secours', function () {
    $staff = User::factory()->create(['access_level' => User::ACCESS_SUPER_ADMIN]);

    $this->actingAs($staff, 'web')->post(route('admin.profile.2fa.start'));
    $secret = session('admin.2fa.pending_secret');
    expect($secret)->not->toBeNull();

    $this->actingAs($staff, 'web')
        ->post(route('admin.profile.2fa.confirm'), ['code' => totpCode($secret)])
        ->assertSessionHas('recovery_codes');

    expect($staff->fresh()->hasTotpEnabled())->toBeTrue();
});
