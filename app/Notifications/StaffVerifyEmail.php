<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Vérification d'e-mail pour un utilisateur du back-office (guard « web »).
 * Construit un lien signé vers la route admin `admin.verification.verify`
 * (distincte de la vérification des comptes participants).
 */
class StaffVerifyEmail extends VerifyEmail
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

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'admin.verification.verify',
            Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    protected function buildMailMessage($url): MailMessage
    {
        $app = (string) config('app.name');
        $minutes = (int) Config::get('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject(__('Vérifiez votre adresse - Back-office :app', ['app' => $app]))
            ->greeting(Greeting::for($this->recipient))
            ->line(__('Confirmez votre adresse e-mail pour activer votre accès au back-office de :app.', ['app' => $app]))
            ->action(__('Vérifier mon adresse'), $url)
            ->line(__('Ce lien de confirmation expire dans :count minutes.', ['count' => $minutes]))
            ->line(__('Si vous n’êtes pas concerné par cette demande, ignorez cet e-mail.'))
            ->salutation(__('L’équipe :app', ['app' => $app]));
    }
}
