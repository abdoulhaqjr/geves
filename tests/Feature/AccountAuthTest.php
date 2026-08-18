<?php

use App\Livewire\Account\Login;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Register;
use App\Models\Account;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

it('inscrit un participant, le connecte et envoie l’e-mail de vérification', function () {
    Notification::fake();

    Livewire::test(Register::class)
        ->set('civility', 'Dr')
        ->set('grade', 'Enseignant-chercheur')
        ->set('first_name', 'Awa')
        ->set('last_name', 'Kodjo')
        ->set('email', 'awa@example.tg')
        ->set('affiliation', 'Université de Kara')
        ->set('password', 'MotDePasse123!')
        ->set('password_confirmation', 'MotDePasse123!')
        ->call('register')
        ->assertRedirect(route('account.dashboard'));

    $account = Account::firstWhere('email', 'awa@example.tg');
    expect($account)->not->toBeNull();
    expect($account->affiliation)->toBe('Université de Kara');
    expect($account->grade)->toBe('Enseignant-chercheur');
    expect(auth('account')->check())->toBeTrue();
    expect($account->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($account, VerifyEmail::class);
});

it('refuse la connexion avec de mauvais identifiants', function () {
    Account::create(['first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'bonpass123']);

    Livewire::test(Login::class)
        ->set('email', 'kofi@example.tg')
        ->set('password', 'mauvais')
        ->call('authenticate')
        ->assertHasErrors('email');

    expect(auth('account')->check())->toBeFalse();
});

it('connecte un participant avec les bons identifiants', function () {
    Account::create(['first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'bonpass123']);

    Livewire::test(Login::class)
        ->set('email', 'kofi@example.tg')
        ->set('password', 'bonpass123')
        ->call('authenticate')
        ->assertRedirect(route('account.dashboard'));

    expect(auth('account')->check())->toBeTrue();
});

it('protège le tableau de bord derrière le guard account', function () {
    $this->get(route('account.dashboard'))->assertRedirect(route('account.login'));
});

it('rend canSubmit vrai une fois le profil complété et l’e-mail vérifié', function () {
    $account = Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345',
    ]);
    $account->markEmailAsVerified();

    Livewire::actingAs($account, 'account')
        ->test(Profile::class)
        ->set('affiliation', 'Université de Kara')
        ->call('save')
        ->assertHasNoErrors();

    expect($account->fresh()->canSubmit())->toBeTrue();
});

it('confirme l’adresse e-mail via le lien signé', function () {
    $account = Account::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345',
    ]);

    $url = URL::temporarySignedRoute('account.verification.verify', Carbon::now()->addHour(), [
        'id' => $account->getKey(),
        'hash' => sha1($account->getEmailForVerification()),
    ]);

    $this->actingAs($account, 'account')->get($url)->assertRedirect(route('account.dashboard'));

    expect($account->fresh()->hasVerifiedEmail())->toBeTrue();
});
