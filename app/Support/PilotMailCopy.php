<?php

namespace App\Support;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

/** Envoie une copie pilote après l'e-mail principal, sans pouvoir bloquer celui-ci. */
class PilotMailCopy
{
    public const DEFAULT_ADDRESS = 'devs@univkara.tg';

    public function handle(MessageSent $event): void
    {
        if (! (bool) Settings::get('mail.pilot_copy_enabled', true)) {
            return;
        }

        $address = trim((string) Settings::get('mail.pilot_copy_address', self::DEFAULT_ADDRESS));
        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $message = $event->sent->getOriginalMessage();
        if (! $message instanceof Email) {
            return;
        }

        foreach ([$message->getTo(), $message->getCc(), $message->getBcc()] as $recipients) {
            foreach ($recipients as $recipient) {
                if (strcasecmp($recipient->getAddress(), $address) === 0) {
                    return;
                }
            }
        }

        // L'enveloppe SMTP ne contient que la boîte pilote : aucun destinataire
        // de l'e-mail original ne reçoit une seconde fois le message.
        try {
            $copy = clone $message;
            $copy->getHeaders()->remove('Bcc');

            Mail::mailer()->getSymfonyTransport()->send(
                $copy,
                new Envelope($event->sent->getEnvelope()->getSender(), [new Address($address)]),
            );
        } catch (Throwable $exception) {
            Log::warning('Copie pilote de l’e-mail non envoyée.', [
                'recipient' => $address,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
