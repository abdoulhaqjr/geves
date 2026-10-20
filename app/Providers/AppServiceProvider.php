<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\User;
use App\Support\Settings;
use App\Support\WindowsSafeFilesystem;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->app->extend('files', fn () => new WindowsSafeFilesystem);
        }
    }

    public function boot(): void
    {
        // Un public/hot laissé par Vite suffit à détourner @vite vers un serveur
        // arrêté ou bloqué et à rendre toutes les pages sans CSS. En local, le
        // manifeste compilé est la source stable, sauf choix explicite du HMR.
        if ($this->app->environment('local')
            && is_file(public_path('build/manifest.json'))
            && ! config('app.vite_use_dev_server')) {
            Vite::useHotFile(storage_path('framework/vite-build-only.hot'));
        }

        $this->applySettingsToConfig();
        $this->limiteLeFilDeVisio();

        // Pagination sur la charte graphique (composant UKC/Tailwise) partout.
        Paginator::defaultView('vendor.pagination.ukc');
        Paginator::defaultSimpleView('vendor.pagination.ukc');

        // RBAC back-office : `manage_colloques` est une super-capacité (staff
        // pleinement habilité / administrateur) qui court-circuite les vérifications
        // de permissions granulaires. Les autres rôles UKC (Comité, Évaluateur,
        // Organisateur, Secrétariat, Comptabilité, Appui, Accueil) sont gouvernés
        // par leurs permissions granulaires (matrice d'accès), appliquées par route.
        //
        // ⚠️ **Le niveau d'accès SYSTÈME passe par ici aussi.** Sans lui, un
        // compte `access_level = super_admin` ouvrait les 16 groupes de routes
        // gardés par `edition.cap:` (qui s'appuient sur `canOnEdition()`, donc sur
        // `isColloqueSuperAdmin()`) mais tombait en 403 sur les 3 gardés par
        // `can:` - Réglages, Utilisateurs & rôles, Témoignages. Le modèle disait
        // « super-administrateur », le portail disait non. `isColloqueSuperAdmin()`
        // est la SEULE source : elle couvre le niveau d'accès, le rôle spatie
        // `administrator` et la permission `manage_colloques`.
        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && $user->isColloqueSuperAdmin()) {
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

        // En-tête public : alimente les liens « Événement en cours » et
        // « Déroulement » (mockup index.html) avec l'événement à la une -
        // celle en cours, sinon la prochaine, sinon la dernière publiée.
        // ⚠️ Les TROIS surfaces qui pointent vers les ancres de l'accueil :
        // l'en-tête, le pied de page et le bouton de la page Déroulement. Une
        // seule oubliée, et le lien mort revient par là.
        View::composer(['partials.public-header', 'partials.public-footer', 'public.deroulement'], function ($view) {
            $event = null;
            $hasLive = false;
            $ongoing = null;
            $upcoming = null;
            $past = null;

            try {
                $published = Event::query()
                    ->whereIn('status', ['open', 'closed', 'archived'])
                    ->orderByDesc('date_start')
                    ->get();

                $ongoing = $published->first(fn (Event $e) => $e->phase()->isOngoing());
                $upcoming = $published
                    ->filter(fn (Event $e) => $e->phase()->publicView() === 'upcoming')
                    ->sortBy('date_start')
                    ->first();

                $past = $published
                    ->first(fn (Event $e) => $e->phase()->publicView() === 'past');

                $event = $ongoing ?? $upcoming ?? $published->first();
                $hasLive = (bool) $ongoing;
            } catch (\Throwable) {
                // Table absente (migrations/installation) : header sans ces liens.
            }

            // ⚠️ Mêmes règles que `HomeController` (`EventPhase::publicView()`),
            // parce que le menu ne doit offrir une ancre que si la section
            // existe vraiment sur la page d'accueil. Un cas Pest compare les
            // deux côtés plutôt que de faire confiance à cette note.
            $view->with('navEdition', $event)
                ->with('navHasLive', $hasLive)
                ->with('navHasOngoing', $ongoing !== null)
                ->with('navHasUpcoming', $upcoming !== null)
                ->with('navHasPast', $past !== null);
        });
    }

    /**
     * Applique les réglages éditables (back-office) à la configuration runtime,
     * en repli sur les valeurs de .env/config. Sans effet si la table n'existe
     * pas encore (migrations, installation).
     */
    /**
     * Débit d'écriture dans le fil d'une salle de visio.
     *
     * ⚠️ La clé n'est PAS l'adresse IP. Une cérémonie publique se suit depuis le
     * campus, donc depuis une poignée d'adresses partagées : un limiteur par IP
     * ferait taire toute une salle parce qu'une personne écrit vite. On compte
     * donc par compte, par membre du staff, ou à défaut par session HTTP.
     */
    private function limiteLeFilDeVisio(): void
    {
        $qui = function (Request $request): string {
            $compte = Auth::guard('account')->id();
            if ($compte) {
                return 'u'.$compte;
            }

            $staff = Auth::guard('web')->id();

            return $staff ? 's'.$staff : 'a'.$request->session()->getId();
        };

        // Écrire : un message toutes les deux secondes en moyenne suffit largement
        // à une discussion, et coupe court à un remplissage automatique.
        RateLimiter::for('visio-chat', fn (Request $request) => Limit::perMinute(30)->by('vchat:'.$qui($request)));

        // Réagir : le geste est plus léger, et se pose puis se retire.
        RateLimiter::for('visio-reaction', fn (Request $request) => Limit::perMinute(60)->by('vreac:'.$qui($request)));

        // ⚠️ **Vérifier un paiement se compte PAR PERSONNE, jamais par IP** - même
        // raison qu'au-dessus, et la conséquence est pire : le parcours direct
        // interroge toutes les six secondes pendant que le payeur compose son
        // code, et un campus règle ses frais depuis une poignée d'adresses. Par
        // IP, le deuxième payeur verrait sa confirmation refusée, sans comprendre.
        RateLimiter::for('paiement-verif', fn (Request $request) => Limit::perMinute(30)->by('pverif:'.$qui($request)));
    }

    private function applySettingsToConfig(): void
    {
        $s = Settings::all();
        if ($s === []) {
            return;
        }

        $set = fn (string $key, mixed $default = null) => $s[$key] ?? $default;

        // Nom de la plateforme (marque affichée partout, e-mails, TOTP…) : source
        // unique surchargée depuis les Réglages, sinon APP_NAME du .env.
        if (filled($set('app.name'))) {
            Config::set('app.name', (string) $set('app.name'));
        }

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
        // ⚠️ **Tout réglage enregistré doit être LISTÉ ICI**, sinon il se saisit,
        // se range en base, se réaffiche dans le formulaire - et n'arrive jamais
        // à la passerelle. Vu sur `public_key` : la clé était bien là, le
        // formulaire embarqué restait muet et le paiement repartait en
        // redirection, sans un mot. Un réglage absent de cette liste ment.
        foreach (['secret', 'mode', 'webhook_secret', 'public_key'] as $k) {
            if (filled($set("fedapay.$k"))) {
                Config::set("services.fedapay.$k", $set("fedapay.$k"));
            }
        }
        // ⚠️ Une case à cocher ne passe PAS par `filled()` : décochée, elle vaut
        // `false`, que `filled()` rejette - le réglage « ne pas proposer la carte »
        // n'aurait alors jamais été appliqué. On regarde si la clé EXISTE.
        if ($set('fedapay.card') !== null) {
            Config::set('services.fedapay.card', (bool) $set('fedapay.card'));
        }

        // Turnstile : la clé publique est éditable ; le secret est chiffré en
        // base et n'est jamais renvoyé au formulaire de réglages.
        if (array_key_exists('turnstile.site_key', $s)) {
            Config::set('services.turnstile.site_key', $set('turnstile.site_key') ?: null);
        }
        if (array_key_exists('turnstile.secret_key', $s)) {
            try {
                Config::set('services.turnstile.secret_key', filled($set('turnstile.secret_key'))
                    ? Crypt::decryptString((string) $set('turnstile.secret_key'))
                    : null);
            } catch (DecryptException) {
                Config::set('services.turnstile.secret_key', null);
            }
        }

        // Bandeau d'information sur les cookies : c'est le réglage en base qui
        // décide, pas le `.env`. ⚠️ Une case à cocher ne passe PAS par `filled()` -
        // décochée elle vaut `false`, que `filled()` rejette, et « ne pas afficher le
        // bandeau » ne serait jamais appliqué (même piège que `fedapay.card`).
        if (array_key_exists('cookies.notice_enabled', $s)) {
            Config::set('cookies.notice_enabled', (bool) $set('cookies.notice_enabled'));
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
     * copie une ligne d'.env entière - « LIVEKIT_API_KEY=abc » - dans le champ).
     * Un nom d'variable est en MAJUSCULES/chiffres/underscore ; les vraies clés,
     * secrets et URL n'ont pas ce motif en tête.
     */
    public static function stripEnvPrefix(string $value): string
    {
        return (string) preg_replace('/^\s*[A-Z][A-Z0-9_]*=/', '', trim($value));
    }
}
