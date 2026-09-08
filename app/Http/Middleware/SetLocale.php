<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Détermine la langue de la requête (i18n) selon l'ordre de priorité :
 * préférence du compte connecté → choix mémorisé en session → langue du
 * navigateur (Accept-Language) → langue par défaut. Applique la locale à
 * l'application pour toute la requête.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        // 1. Préférence explicite du compte participant connecté.
        $account = Auth::guard('account')->user();
        if ($account && Locales::supported($account->locale)) {
            return (string) $account->locale;
        }

        // 2. Choix mémorisé en session (invité ou staff).
        $session = $request->session()->get('locale');
        if (Locales::supported($session)) {
            return (string) $session;
        }

        // 3. Langue préférée du navigateur, si prise en charge.
        $browser = $request->getPreferredLanguage(Locales::codes());
        if (Locales::supported($browser)) {
            return (string) $browser;
        }

        // 4. Repli : langue par défaut.
        return Locales::default();
    }
}
