<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\CookieConsent;
use Illuminate\Contracts\View\View;

/**
 * Page publique « Cookies » : l'inventaire de ce que la plateforme dépose.
 *
 * ⚠️ Elle est la SUBSTANCE du bandeau. Un bandeau qui affirme « uniquement le
 * nécessaire » sans rien derrière demande qu'on le croie sur parole ; la liste,
 * construite depuis la configuration réelle ({@see CookieConsent}),
 * se vérifie ligne à ligne contre ce que le navigateur reçoit.
 *
 * ⚠️ Aucune donnée du visiteur n'entre dans ce rendu : la page reste identique
 * pour tout le monde, donc cacheable comme les autres pages publiques.
 */
class CookieController extends Controller
{
    public function __invoke(): View
    {
        return view('public.cookies');
    }
}
