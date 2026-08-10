<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->applySettingsToConfig();

        // Les comptes participants utilisent des routes dédiées (guard « account ») :
        // on réécrit les URL générées par les notifications Laravel en conséquence.

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return route('account.password.reset', ['token' => $token])
                . '?email=' . urlencode($notifiable->getEmailForPasswordReset());
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'account.verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }

    /**
     * Applique les réglages éditables (back-office) à la configuration runtime,
     * en repli sur les valeurs de .env/config. Sans effet si la table n'existe
     * pas encore (migrations, installation).
     */
    protected function applySettingsToConfig(): void
    {
        $s = Settings::all();
        if ($s === []) {
            return;
        }

        $set = fn (string $key, mixed $default = null) => $s[$key] ?? $default;

        // Expéditeur & devise
        Config::set('mail.from.name', $set('mail.from_name', config('mail.from.name')));
        Config::set('mail.from.address', $set('mail.from_address', config('mail.from.address')));
        Config::set('payments.currency', $set('payments.currency', config('payments.currency')));

        // SMTP (seulement si un hôte est renseigné)
        if (filled($set('mail.host'))) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $set('mail.host'));
            Config::set('mail.mailers.smtp.port', (int) $set('mail.port', 587));
            Config::set('mail.mailers.smtp.username', $set('mail.username'));
            Config::set('mail.mailers.smtp.password', $set('mail.password'));
            Config::set('mail.mailers.smtp.encryption', $set('mail.encryption', 'tls') ?: null);
        }

        // Passerelle par défaut
        Config::set('payments.default', $set('payments.default', config('payments.default')));

        // Identifiants passerelles (repli sur config/.env)
        foreach (['username', 'password', 'client_secret', 'api_key', 'base_url'] as $k) {
            if (filled($set("cashpay.$k"))) {
                Config::set("services.cashpay.$k", $set("cashpay.$k"));
            }
        }
        foreach (['secret', 'mode', 'webhook_secret'] as $k) {
            if (filled($set("fedapay.$k"))) {
                Config::set("services.fedapay.$k", $set("fedapay.$k"));
            }
        }
    }
}
