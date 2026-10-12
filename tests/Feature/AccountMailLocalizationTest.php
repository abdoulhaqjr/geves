<?php

use App\Models\User;
use App\Notifications\AccountResetPassword;
use App\Notifications\AccountVerifyEmail;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;

/**
 * Les e-mails de compte (confirmation, mot de passe oublié) sont rédigés et
 * LOCALISÉS : français par défaut, langue de la personne si elle en a choisi une.
 * ⚠️ Avant, c'étaient les notifications par défaut de Laravel, en anglais.
 */
it('la langue des e-mails suit le compte, défaut français', function () {
    expect((new User(['locale' => 'en']))->preferredLocale())->toBe('en')
        ->and((new User(['locale' => 'fr']))->preferredLocale())->toBe('fr')
        ->and((new User(['locale' => null]))->preferredLocale())->toBe('fr')
        ->and((new User(['locale' => 'de']))->preferredLocale())->toBe('fr'); // non prise en charge -> défaut
});

it('envoie l’e-mail de confirmation localisé à l’inscription', function () {
    Notification::fake();

    $user = User::factory()->create(['locale' => null]);
    $user->sendEmailVerificationNotification();

    Notification::assertSentTo($user, AccountVerifyEmail::class);
});

it('rédige la confirmation en français, et en anglais pour un compte anglophone', function () {
    $user = User::factory()->create();

    App::setLocale('fr');
    $fr = (new AccountVerifyEmail)->toMail($user);
    expect($fr->subject)->toContain('Confirmez votre adresse e-mail')
        ->and(implode(' ', $fr->introLines))->toContain('Votre compte vient d’être créé');

    App::setLocale('en');
    $en = (new AccountVerifyEmail)->toMail($user);
    expect($en->subject)->toContain('Confirm your email address')
        ->and($en->actionText)->toBe('Confirm my email address');

    App::setLocale('fr');
});

it('rédige la réinitialisation de mot de passe en français et en anglais', function () {
    $user = User::factory()->create();

    Notification::fake();
    $user->sendPasswordResetNotification('jeton-de-test');
    Notification::assertSentTo($user, AccountResetPassword::class);

    App::setLocale('fr');
    $fr = (new AccountResetPassword('jeton'))->toMail($user);
    expect($fr->subject)->toContain('Réinitialisation de votre mot de passe')
        ->and($fr->actionText)->toBe('Réinitialiser mon mot de passe');

    App::setLocale('en');
    $en = (new AccountResetPassword('jeton'))->toMail($user);
    expect($en->subject)->toContain('Reset your password')
        ->and($en->actionText)->toBe('Reset my password');

    App::setLocale('fr');
});
