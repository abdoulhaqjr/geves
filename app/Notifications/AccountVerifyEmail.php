<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

/**
 * Vérification d'e-mail d'un compte participant (guard « account »), envoyée à
 * l'inscription : c'est le premier message que reçoit un nouvel inscrit, elle
 * fait donc aussi office de bienvenue.
 *
 * ⚠️ Rédigée et LOCALISÉE (défaut français, anglais via lang/en.json) : la
 * notification par défaut de Laravel sortait en anglais générique, quelle que
 * soit la langue de la personne. L'URL signée vers la route participant reste
 * fournie par VerifyEmail::createUrlUsing (AppServiceProvider) - seule la
 * rédaction est redéfinie ici. La langue de rendu est celle du destinataire
 * (User::preferredLocale), repli français.
 */
class AccountVerifyEmail extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        $app = (string) config('app.name');
        $minutes = (int) Config::get('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject(__('Confirmez votre adresse e-mail - :app', ['app' => $app]))
            ->greeting(__('Bienvenue sur :app !', ['app' => $app]))
            ->line(__('Votre compte vient d’être créé. Il reste une étape pour l’activer : confirmer votre adresse e-mail. Elle sécurise votre compte et vous ouvre l’ensemble des services (soumissions, inscriptions, documents).'))
            ->action(__('Confirmer mon adresse'), $url)
            ->line(__('Ce lien de confirmation expire dans :count minutes.', ['count' => $minutes]))
            ->line(__('Si vous n’êtes pas à l’origine de cette inscription, ignorez simplement cet e-mail : aucun compte ne sera activé sans cette confirmation.'))
            ->salutation(__('L’équipe :app', ['app' => $app]));
    }
}
