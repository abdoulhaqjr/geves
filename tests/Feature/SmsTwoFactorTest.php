<?php

use App\Models\User;
use App\Sms\SmsManager;
use Illuminate\Support\Facades\Auth;

/** Faux fournisseur SMS : capture les messages au lieu de les envoyer. */
function fakeSms(): SmsManager
{
    $fake = new class extends SmsManager
    {
        /** @var list<array{to: string, message: string}> */
        public array $sent = [];

        public function send(string $to, string $message): void
        {
            $this->sent[] = ['to' => $to, 'message' => $message];
        }
    };

    app()->instance(SmsManager::class, $fake);

    return $fake;
}

function otpFrom(SmsManager $fake): string
{
    $msg = end($fake->sent)['message'] ?? '';
    preg_match('/\b(\d{6})\b/', $msg, $m);

    return $m[1] ?? '';
}

function verifiedAccountForSms(): User
{
    return User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'MotDePasse123!', 'affiliation' => 'UK', 'email_verified_at' => now(),
    ]);
}

it('active la 2FA par SMS : numéro → code → confirmation', function () {
    $fake = fakeSms();
    $account = verifiedAccountForSms();

    $this->actingAs($account, 'account')->post(route('account.security.sms.start'));
    $this->actingAs($account, 'account')
        ->post(route('account.security.sms.send'), ['phone' => '+228 90 11 22 33'])
        ->assertSessionHasNoErrors();

    expect($fake->sent)->toHaveCount(1);
    expect($fake->sent[0]['to'])->toBe('+228 90 11 22 33');

    $this->actingAs($account, 'account')
        ->post(route('account.security.sms.confirm'), ['code' => otpFrom($fake)])
        ->assertSessionHasNoErrors();

    $account->refresh();
    expect($account->hasSmsEnabled())->toBeTrue();
    expect($account->twoFactorMethod())->toBe('sms');
    expect($account->phone)->toBe('+228 90 11 22 33');
    expect($account->countRecoveryCodes())->toBe(8);
});

it('rejette un mauvais code à l’activation SMS', function () {
    $fake = fakeSms();
    $account = verifiedAccountForSms();

    $this->actingAs($account, 'account')->post(route('account.security.sms.start'));
    $this->actingAs($account, 'account')->post(route('account.security.sms.send'), ['phone' => '+22890000000']);
    $this->actingAs($account, 'account')
        ->post(route('account.security.sms.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($account->fresh()->hasSmsEnabled())->toBeFalse();
});

it('exige un OTP SMS à la connexion et finalise après vérification', function () {
    $fake = fakeSms();
    $account = verifiedAccountForSms();
    $account->forceFill([
        'phone' => '+22890112233',
        'two_factor_method' => 'sms',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post(route('account.login.store'), [
        'email' => 'awa@example.tg',
        'password' => 'MotDePasse123!',
    ])->assertRedirect(route('account.login')); // renvoi vers l'écran de second facteur

    expect($fake->sent)->toHaveCount(1); // OTP envoyé

    // L'écran de connexion affiche bien le second facteur (méthode SMS).
    $this->get(route('account.login'))->assertOk()->assertSee('Vérification en deux étapes');

    $this->post(route('account.login.confirm'), ['code' => otpFrom($fake)])
        ->assertRedirect(route('account.dashboard'));

    expect(Auth::guard('account')->check())->toBeTrue();
});

it('active SMS désactive le TOTP (méthode exclusive)', function () {
    $fake = fakeSms();
    $account = verifiedAccountForSms();
    // TOTP déjà actif.
    $account->forceFill([
        'two_factor_secret' => 'SECRETBASE32AAAA',
        'two_factor_confirmed_at' => now(),
    ])->save();
    expect($account->hasTotpEnabled())->toBeTrue();

    $this->actingAs($account, 'account')->post(route('account.security.sms.start'));
    $this->actingAs($account, 'account')->post(route('account.security.sms.send'), ['phone' => '+22890000000']);
    $this->actingAs($account, 'account')
        ->post(route('account.security.sms.confirm'), ['code' => otpFrom($fake)])
        ->assertSessionHasNoErrors();

    $account->refresh();
    expect($account->hasTotpEnabled())->toBeFalse();
    expect($account->hasSmsEnabled())->toBeTrue();
});
