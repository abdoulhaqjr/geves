<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureEditionAccess;
use App\Http\Middleware\PrefixedCsrfCookie;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\StartSessionWithLegacyCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Autorisation des canaux temps réel (rédaction à plusieurs des documents
    // de tâche). ⚠️ Le point d'entrée `/broadcasting/auth` passe par la session
    // du back-office : il porte la pile `web`, pas `api` - sans elle, aucun
    // compte n'est reconnu et toute demande de canal est refusée.
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Page de connexion unique (participants + staff) : tous les invités y sont menés.
        $middleware->redirectGuestsTo(fn () => route('account.login'));

        // Les webhooks de paiement sont des appels serveur-à-serveur (pas de CSRF).
        $middleware->validateCsrfTokens(except: ['webhooks/*', 'csp-report']);

        $middleware->web(replace: [
            StartSession::class => StartSessionWithLegacyCookie::class,
            PreventRequestForgery::class => PrefixedCsrfCookie::class,
        ]);

        // Langue de la requête (i18n) : compte → session → navigateur → défaut.
        $middleware->web(append: SetLocale::class);
        // Coupe aussi les sessions désactivées sur les pages publiques et les routes partagées.
        $middleware->web(append: EnsureActiveUser::class);
        $middleware->web(append: ContentSecurityPolicy::class);

        // Étanchéité d'accès par événement (staff affecté à un colloque).
        $middleware->alias([
            'edition.cap' => EnsureEditionAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rendu JSON des exceptions pour les appels d'API et tout appel qui
        // attend du JSON (endpoints fetch : assistante Gesco, etc.).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
