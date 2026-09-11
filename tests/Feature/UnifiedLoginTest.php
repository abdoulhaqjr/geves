<?php

use App\Models\User;

/**
 * Page de connexion unique : /connexion authentifie aussi bien un participant
 * (guard « account ») qu'un membre du personnel (guard « web »), selon les
 * identifiants saisis. /admin/connexion redirige vers cette page unique.
 */
it('connecte un participant via la page unique', function () {
    $account = User::create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'password' => 'pass12345', 'grade' => 'Doctorante', 'affiliation' => 'UK',
    ]);
    $account->markEmailAsVerified();

    $this->post(route('account.login.store'), [
        'email' => 'awa@example.tg', 'password' => 'pass12345',
    ])->assertRedirect(route('account.dashboard'));

    expect(auth('account')->check())->toBeTrue();
    expect(auth('web')->check())->toBeFalse();
});

it('connecte le staff via la même page unique', function () {
    $staff = adminStaff(); // User + permission manage_colloques
    $staff->forceFill(['password' => bcrypt('secret-staff')])->save();

    $this->post(route('account.login.store'), [
        'email' => $staff->email, 'password' => 'secret-staff',
    ])->assertRedirect(route('admin.dashboard'));

    expect(auth('web')->check())->toBeTrue();
    expect(auth('account')->check())->toBeFalse();
});

it('rejette des identifiants inconnus', function () {
    $this->post(route('account.login.store'), [
        'email' => 'nobody@example.tg', 'password' => 'wrong-pass',
    ])->assertSessionHasErrors('email');

    expect(auth('web')->check())->toBeFalse();
    expect(auth('account')->check())->toBeFalse();
});

it('redirige /admin/connexion vers la page de connexion unique', function () {
    $this->get(route('admin.login'))->assertRedirect(route('account.login'));
});
