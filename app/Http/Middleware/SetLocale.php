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
 * préférence du compte connecté → choix mémorisé en session → langue par
 * défaut (français). La langue n'est pas déduite du navigateur : le français
 * reste l'expérience par défaut d'une institution francophone, l'anglais étant
 * choisi explicitement via le sélecteur de langue.
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

        // 3. Repli : langue par défaut (français).
        return Locales::default();
    }
}
