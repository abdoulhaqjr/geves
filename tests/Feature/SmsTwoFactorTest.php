<?php

use App\Livewire\Account\Login;
use App\Livewire\Account\Security;
use App\Models\Account;
use App\Sms\SmsManager;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

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

function verifiedAccountForSms(): Account
{
    return Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'MotDePasse123!', 'affiliation' => 'UK', 'email_verified_at' => now(),
    ]);
}

it('active la 2FA par SMS : numéro → code → confirmation', function () {
    $fake = fakeSms();
    $account = verifiedAccountForSms();

    $comp = Livewire::actingAs($account, 'account')->test(Security::class)
        ->call('startSmsSetup')
        ->set('phone', '+228 90 11 22 33')
        ->call('sendSmsSetupCode')
        ->assertHasNoErrors();

    expect($fake->sent)->toHaveCount(1);
    expect($fake->sent[0]['to'])->toBe('+228 90 11 22 33');

    $comp->set('code', otpFrom($fake))
        ->call('confirmSmsSetup')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->hasSmsEnabled())->toBeTrue();
    expect($account->twoFactorMethod())->toBe('sms');
    expect($account->phone)->toBe('+228 90 11 22 33');
    expect($account->countRecoveryCodes())->toBe(8);
});

it('rejette un mauvais code à l’activation SMS', function () {
    $fake = fakeSms();
    $account = verifiedAccountForSms();

    Livewire::actingAs($account, 'account')->test(Security::class)
        ->call('startSmsSetup')
        ->set('phone', '+22890000000')
        ->call('sendSmsSetupCode')
        ->set('code', '000000')
        ->call('confirmSmsSetup')
        ->assertHasErrors('code');

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

    $comp = Livewire::test(Login::class)
        ->set('email', 'awa@example.tg')
        ->set('password', 'MotDePasse123!')
        ->call('authenticate')
        ->assertSet('twoFactor', true)
        ->assertSet('method', 'sms');

    expect($fake->sent)->toHaveCount(1); // OTP envoyé

    $comp->set('code', otpFrom($fake))
        ->call('confirmChallenge')
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

    Livewire::actingAs($account, 'account')->test(Security::class)
        ->call('startSmsSetup')
        ->set('phone', '+22890000000')
        ->call('sendSmsSetupCode')
        ->set('code', otpFrom($fake))
        ->call('confirmSmsSetup')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->hasTotpEnabled())->toBeFalse();
    expect($account->hasSmsEnabled())->toBeTrue();
});
