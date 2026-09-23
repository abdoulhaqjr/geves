<?php

namespace App\Sms\Providers;

use App\Sms\Contracts\SmsProvider;
use Illuminate\Support\Facades\Http;

/**
 * Fournisseur SMS générique par API HTTP (POST JSON). Convient à de nombreuses
 * passerelles (dont locales) : on configure l'URL, le jeton Bearer et l'ID
 * expéditeur ; le corps envoyé est { to, message, sender }. Les identifiants
 * proviennent de la config (réglages back-office / .env) - jamais du dépôt.
 */
class HttpSmsProvider implements SmsProvider
{
    public function key(): string
    {
        return 'http';
    }

    public function label(): string
    {
        return 'Passerelle HTTP (API)';
    }

    public function isAvailable(): bool
    {
        return filled(config('sms.http.url')) && filled(config('sms.http.token'));
    }

    public function send(string $to, string $message): void
    {
        $response = Http::withToken((string) config('sms.http.token'))
            ->acceptJson()
            ->timeout(15)
            ->post((string) config('sms.http.url'), [
                'to' => $to,
                'message' => $message,
                'sender' => (string) config('sms.http.sender', 'UKColloques'),
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Échec de l’envoi du SMS (HTTP '.$response->status().').');
        }
    }
}
