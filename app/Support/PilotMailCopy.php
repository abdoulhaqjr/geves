<?php

namespace App\Support;

use Illuminate\Mail\Events\MessageSending;

/** Ajoute la boîte de contrôle en copie cachée à chaque e-mail sortant. */
class PilotMailCopy
{
    public const DEFAULT_ADDRESS = 'dev@univkara.tg';

    public function handle(MessageSending $event): void
    {
        if (! (bool) Settings::get('mail.pilot_copy_enabled', true)) {
            return;
        }

        $address = trim((string) Settings::get('mail.pilot_copy_address', self::DEFAULT_ADDRESS));
        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $message = $event->message;
        foreach ([$message->getTo(), $message->getCc(), $message->getBcc()] as $recipients) {
            foreach ($recipients as $recipient) {
                if (strcasecmp($recipient->getAddress(), $address) === 0) {
                    return;
                }
            }
        }

        $message->addBcc($address);
    }
}
