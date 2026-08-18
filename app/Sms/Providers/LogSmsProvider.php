<?php

namespace App\Sms\Providers;

use App\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur SMS de développement : n'envoie rien mais journalise le message
 * (canal par défaut). Équivalent de MAIL_MAILER=log pour les SMS. Toujours
 * disponible — c'est le repli du registre quand aucun vrai fournisseur n'est
 * configuré.
 */
class LogSmsProvider implements SmsProvider
{
    public function key(): string
    {
        return 'log';
    }

    public function label(): string
    {
        return 'Journal (développement)';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function send(string $to, string $message): void
    {
        Log::info('SMS (log) → '.$to.' : '.$message);
    }
}
