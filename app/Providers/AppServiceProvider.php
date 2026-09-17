<?php

namespace App\Providers;

use App\Models\Edition;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Pagination sur la charte graphique (composant UKC/Tailwise) partout.
        Paginator::defaultView('vendor.pagination.ukc');
        Paginator::defaultSimpleView('vendor.pagination.ukc');

        // RBAC back-office : `manage_colloques` est une super-capacité (staff
        // pleinement habilité / administrateur) qui court-circuite les vérifications
        // de permissions granulaires. Les autres rôles UKC (Comité, Relecteur,
        // Organisateur, Secrétariat, Comptabilité, Appui, Accueil) sont gouvernés
        // par leurs permissions granulaires (matrice d'accès), appliquées par route.
        Gate::before(function ($user, string $ability) {
            if ($user instanceof User
                && ($user->hasRole('administrator') || $user->getAllPermissions()->contains('name', 'manage_colloques'))) {
                return true;
            }

            return null;
        });

        // Les effets de bord du changement de statut (NotifyAuthorOfStatus,
        // InviteCoauthorsOnAcceptance) sont câblés par l'auto-découverte des
        // listeners de app/Listeners (SubmissionStatusChanged).

        // Les comptes participants utilisent des routes dédiées (guard « account ») :
        // on réécrit les URL générées par les notifications Laravel en conséquence.

        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            return route('account.password.reset', ['token' => $token])
                .'?email='.urlencode($notifiable->getEmailForPasswordReset());
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

        // En-tête public : alimente les liens « Édition en cours » et
        // « Déroulement » (mockup index.html) avec l'édition à la une —
        // celle en cours, sinon la prochaine, sinon la dernière publiée.
        View::composer('partials.public-header', function ($view) {
            $edition = null;
            $hasLive = false;

            try {
                $published = Edition::query()
                    ->whereIn('status', ['open', 'closed', 'archived'])
                    ->orderByDesc('date_start')
                    ->get();

                $ongoing = $published->first(fn (Edition $e) => $e->phase()->isOngoing());
                $upcoming = $published
                    ->filter(fn (Edition $e) => $e->phase()->publicView() === 'upcoming')
                    ->sortBy('date_start')
                    ->first();

                $edition = $ongoing ?? $upcoming ?? $published->first();
                $hasLive = (bool) $ongoing;
            } catch (\Throwable) {
                // Table absente (migrations/installation) : header sans ces liens.
            }

            $view->with('navEdition', $edition)->with('navHasLive', $hasLive);
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

        // URL du site (source unique pour les liens des e-mails/notifs, y compris
        // en file d'attente où il n'y a pas d'hôte de requête).
        if (filled($set('app.url'))) {
            $appUrl = (string) $set('app.url');
            Config::set('app.url', $appUrl);
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Expéditeur & devise
        Config::set('mail.from.name', $set('mail.from_name', config('mail.from.name')));
        Config::set('mail.from.address', $set('mail.from_address', config('mail.from.address')));
        Config::set('payments.currency', $set('payments.currency', config('payments.currency')));

        // SMTP : identifiants toujours chargés si un hôte est renseigné.
        if (filled($set('mail.host'))) {
            Config::set('mail.mailers.smtp.host', $set('mail.host'));
            Config::set('mail.mailers.smtp.port', (int) $set('mail.port', 587));
            Config::set('mail.mailers.smtp.username', $set('mail.username'));
            Config::set('mail.mailers.smtp.password', $set('mail.password'));
            Config::set('mail.mailers.smtp.encryption', $set('mail.encryption', 'tls') ?: null);
        }

        // Transport e-mail : les Réglages sont la source de vérité (mail.mailer).
        // À défaut de réglage : MAIL_MAILER=log du .env reste prioritaire, sinon
        // SMTP si un hôte est renseigné.
        $mailer = (string) $set('mail.mailer');
        if (in_array($mailer, ['log', 'smtp'], true)) {
            Config::set('mail.default', $mailer);
        } elseif (filled($set('mail.host')) && config('mail.default') !== 'log') {
            Config::set('mail.default', 'smtp');
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

        // Fournisseurs d'IA : clé + modèle par défaut
        foreach (['openai', 'anthropic', 'gemini', 'grok'] as $svc) {
            if (filled($set("ai.{$svc}_key"))) {
                Config::set("services.$svc.key", $set("ai.{$svc}_key"));
            }
            if (filled($set("ai.{$svc}_model"))) {
                Config::set("services.$svc.model", $set("ai.{$svc}_model"));
            }
            // Paliers économie / qualité (facultatifs) : choisis automatiquement selon la tâche.
            foreach (['fast', 'deep'] as $tier) {
                if (filled($set("ai.{$svc}_model_{$tier}"))) {
                    Config::set("services.$svc.model_{$tier}", $set("ai.{$svc}_model_{$tier}"));
                }
            }
        }
        Config::set('services.ai.default', $set('ai.default', config('services.ai.default')));

        // Visio : fournisseur par défaut + identifiants LiveKit (repli config/.env).
        // On tolère un collage maladroit du type « LIVEKIT_API_KEY=xxx » : le
        // préfixe « NOM= » est retiré pour ne garder que la valeur.
        Config::set('meetings.default', $set('meetings.default', config('meetings.default')));
        foreach (['url', 'api_key', 'api_secret', 'meet_url'] as $k) {
            if (filled($set("livekit.$k"))) {
                Config::set("meetings.livekit.$k", self::stripEnvPrefix((string) $set("livekit.$k")));
            }
        }
        foreach (['s3_bucket', 's3_region', 's3_access_key', 's3_secret', 's3_endpoint'] as $k) {
            if (filled($set("livekit_egress.$k"))) {
                Config::set("meetings.livekit.egress.$k", $set("livekit_egress.$k"));
            }
        }

        // HAL : autocomplétion des affiliations (activable dans les réglages).
        if ($set('hal.enabled') !== null) {
            Config::set('services.hal.enabled', (bool) $set('hal.enabled'));
        }

        // SMS (2FA par OTP) : fournisseur par défaut + passerelle HTTP.
        Config::set('sms.default', $set('sms.default', config('sms.default')));
        foreach (['url', 'token', 'sender'] as $k) {
            if (filled($set("sms.http_$k"))) {
                Config::set("sms.http.$k", $set("sms.http_$k"));
            }
        }
    }

    /**
     * Retire un préfixe « NOM= » collé par erreur devant une valeur (ex. quand on
     * copie une ligne d'.env entière — « LIVEKIT_API_KEY=abc » — dans le champ).
     * Un nom d'variable est en MAJUSCULES/chiffres/underscore ; les vraies clés,
     * secrets et URL n'ont pas ce motif en tête.
     */
    public static function stripEnvPrefix(string $value): string
    {
        return (string) preg_replace('/^\s*[A-Z][A-Z0-9_]*=/', '', trim($value));
    }
}
