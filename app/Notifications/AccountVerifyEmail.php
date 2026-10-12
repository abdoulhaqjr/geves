<?php

namespace App\Notifications;

use App\Models\User;
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
 *
 * L'accueil est personnalisé par le nom quand il est connu (« Bienvenue sur
 * GEVES, Dr Awa Doumbia ! ») - à l'inscription, il l'est toujours.
 */
class AccountVerifyEmail extends VerifyEmail
{
    /** Destinataire, capté dans toMail pour personnaliser l'accueil. */
    protected ?User $recipient = null;

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $this->recipient = $notifiable instanceof User ? $notifiable : null;

        return parent::toMail($notifiable);
    }

    protected function buildMailMessage($url): MailMessage
    {
        $app = (string) config('app.name');
        $minutes = (int) Config::get('auth.verification.expire', 60);
        $name = $this->recipient?->salutationName();

        $greeting = $name !== null
            ? __('Bienvenue sur :app, :name !', ['app' => $app, 'name' => $name])
            : __('Bienvenue sur :app !', ['app' => $app]);

        return (new MailMessage)
            ->subject(__('Confirmez votre adresse e-mail - :app', ['app' => $app]))
            ->greeting($greeting)
            ->line(__('Votre compte vient d’être créé. Il reste une étape pour l’activer : confirmer votre adresse e-mail. Elle sécurise votre compte et vous ouvre l’ensemble des services (soumissions, inscriptions, documents).'))
            ->action(__('Confirmer mon adresse'), $url)
            ->line(__('Ce lien de confirmation expire dans :count minutes.', ['count' => $minutes]))
            ->line(__('Si vous n’êtes pas à l’origine de cette inscription, ignorez simplement cet e-mail : aucun compte ne sera activé sans cette confirmation.'))
            ->salutation(__('L’équipe :app', ['app' => $app]));
    }
}
