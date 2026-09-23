<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Connexion du personnel de gestion. Il n'existe plus de page dédiée : la
 * plateforme a une **page de connexion unique** (`account.login`) qui détecte
 * automatiquement participant ou staff selon les identifiants. Cette route est
 * conservée (nom `admin.login`) comme redirection de compatibilité - utilisée
 * par la déconnexion admin, la vérification d'e-mail staff et d'anciens liens.
 */
class LoginController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('account.login');
    }
}
