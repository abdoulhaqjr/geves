<?php

namespace App\Support;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
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
            $originalRecipients = array_map(
                static fn (Address $recipient): string => $recipient->getAddress(),
                [...$message->getTo(), ...$message->getCc()],
            );
            $copy->to($address);
            $copy->getHeaders()->remove('Cc');
            $copy->getHeaders()->remove('Bcc');
            $copy->getHeaders()->remove('Message-ID');
            if ($originalRecipients !== []) {
                $copy->getHeaders()->addTextHeader('X-GEVES-Original-To', implode(', ', $originalRecipients));
            }

            $sent = Mail::mailer($event->data['mailer'] ?? null)->getSymfonyTransport()->send(
                $copy,
                new Envelope($event->sent->getEnvelope()->getSender(), [new Address($address)]),
            );
            if ($sent === null) {
                throw new RuntimeException('Le transport a refusé la copie.');
            }

            Log::info('Copie pilote acceptée par le transport e-mail.', [
                'recipient' => $address,
                'original_message_id' => $event->sent->getMessageId(),
                'copy_message_id' => $sent->getMessageId(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Copie pilote de l’e-mail non envoyée.', [
                'recipient' => $address,
                'original_message_id' => $event->sent->getMessageId(),
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
