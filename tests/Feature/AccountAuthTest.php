<?php

use App\Models\Account;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use App\Models\User;

it('inscrit un participant, le connecte et envoie l’e-mail de vérification', function () {
    Notification::fake();

    $this->post(route('account.register.store'), [
        'civility' => 'Dr',
        'grade' => 'Enseignant-chercheur',
        'first_name' => 'Awa',
        'last_name' => 'Kodjo',
        'email' => 'awa@example.tg',
        'affiliation' => 'Université de Kara',
        'password' => 'MotDePasse123!',
        'password_confirmation' => 'MotDePasse123!',
        'terms' => '1',
    ])->assertRedirect(route('account.dashboard'));

    $account = User::firstWhere('email', 'awa@example.tg');
    expect($account)->not->toBeNull();
    expect($account->affiliation)->toBe('Université de Kara');
    expect($account->grade)->toBe('Enseignant-chercheur');
    expect(auth('account')->check())->toBeTrue();
    expect($account->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($account, VerifyEmail::class);
});

it('refuse la connexion avec de mauvais identifiants', function () {
    User::create(['first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'bonpass123']);

    $this->post(route('account.login.store'), [
        'email' => 'kofi@example.tg',
        'password' => 'mauvais',
    ])->assertSessionHasErrors('email');

    expect(auth('account')->check())->toBeFalse();
});

it('connecte un participant avec les bons identifiants', function () {
    User::create(['first_name' => 'Kofi', 'last_name' => 'M', 'email' => 'kofi@example.tg', 'password' => 'bonpass123']);

    $this->post(route('account.login.store'), [
        'email' => 'kofi@example.tg',
        'password' => 'bonpass123',
    ])->assertRedirect(route('account.dashboard'));

    expect(auth('account')->check())->toBeTrue();
});

it('protège le tableau de bord derrière le guard account', function () {
    $this->get(route('account.dashboard'))->assertRedirect(route('account.login'));
});

it('rend canSubmit vrai une fois le profil complété et l’e-mail vérifié', function () {
    $account = User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345',
    ]);
    $account->markEmailAsVerified();

    $this->actingAs($account, 'account')->put(route('account.profile.update'), [
        'first_name' => $account->first_name,
        'last_name' => $account->last_name,
        'affiliation' => 'Université de Kara',
    ])->assertSessionHasNoErrors();

    expect($account->fresh()->canSubmit())->toBeTrue();
});

it('confirme l’adresse e-mail via le lien signé', function () {
    $account = User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'password' => 'pass12345',
    ]);

    $url = URL::temporarySignedRoute('account.verification.verify', Carbon::now()->addHour(), [
        'id' => $account->getKey(),
        'hash' => sha1($account->getEmailForVerification()),
    ]);

    $this->actingAs($account, 'account')->get($url)->assertRedirect(route('account.dashboard'));

    expect($account->fresh()->hasVerifiedEmail())->toBeTrue();
});
