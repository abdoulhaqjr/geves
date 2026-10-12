<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class Turnstile
{
    public static function configured(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret_key'));
    }

    public static function validate(Request $request, string $action): void
    {
        // Anti-bot OPTIONNEL : sans clés (dev, test, ou prod pas encore réglée), on
        // n'exige rien - sinon l'absence de configuration bloquerait TOUTE
        // inscription/tout message de contact. La protection s'active dès que les
        // clés Turnstile sont renseignées (Réglages / .env).
        if (! self::configured()) {
            return;
        }

        $token = $request->input('cf-turnstile-response');

        if (! is_string($token) || $token === '' || strlen($token) > 2048) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Veuillez effectuer la vérification de sécurité.',
            ]);
        }

        try {
            $response = Http::asForm()->timeout(8)->post(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'secret' => config('services.turnstile.secret_key'),
                    'response' => $token,
                ],
            );
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'La vérification de sécurité a échoué. Réessayez dans un instant.',
            ]);
        }

        if (! $response->successful()
            || $response->json('success') !== true
            || $response->json('action') !== $action
            || $response->json('hostname') !== $request->getHost()) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'La vérification de sécurité a expiré ou a échoué. Rechargez la page et réessayez.',
            ]);
        }
    }
}
