<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * Réinitialisation de mot de passe (comptes unifiés : participants ET staff,
 * même modèle User). Envoyée par User::sendPasswordResetNotification.
 *
 * ⚠️ Rédigée et LOCALISÉE (défaut français, anglais via lang/en.json) : la
 * notification par défaut de Laravel sortait en anglais générique. L'URL (route
 * participant + e-mail) reste fournie par ResetPassword::createUrlUsing
 * (AppServiceProvider) - seule la rédaction est redéfinie ici. La langue de
 * rendu est celle du destinataire (User::preferredLocale), repli français.
 */
class AccountResetPassword extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $app = (string) config('app.name');
        // Le courtier réellement utilisé est « users » (Password::broker('users')).
        $minutes = (int) Config::get('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject(__('Réinitialisation de votre mot de passe - :app', ['app' => $app]))
            ->greeting(__('Bonjour,'))
            ->line(__('Vous recevez cet e-mail car une réinitialisation du mot de passe a été demandée pour votre compte :app.', ['app' => $app]))
            ->action(__('Réinitialiser mon mot de passe'), $url)
            ->line(__('Ce lien de réinitialisation expire dans :count minutes.', ['count' => $minutes]))
            ->line(__('Si vous n’avez pas demandé cette réinitialisation, aucune action n’est requise : votre mot de passe reste inchangé.'))
            ->salutation(__('L’équipe :app', ['app' => $app]));
    }
}
