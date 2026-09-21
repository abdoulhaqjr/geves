<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('Vérifiez votre adresse — Back-office GEVES')
            ->greeting('Bonjour,')
            ->line('Confirmez votre adresse e-mail pour activer votre accès au back-office GEVES.')
            ->action('Vérifier mon adresse', $url)
            ->line('Si vous n’êtes pas concerné, ignorez cet e-mail.');
    }
}
