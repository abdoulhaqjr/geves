<?php

use App\Http\Middleware\EnsureEditionAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Page de connexion unique (participants + staff) : tous les invités y sont menés.
        $middleware->redirectGuestsTo(fn () => route('account.login'));

        // Les webhooks de paiement sont des appels serveur-à-serveur (pas de CSRF).
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        // Étanchéité d'accès par édition (staff affecté à un colloque).
        $middleware->alias(['edition.cap' => EnsureEditionAccess::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rendu JSON des exceptions pour les appels d'API et tout appel qui
        // attend du JSON (endpoints fetch : assistante Gesco, etc.).
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
