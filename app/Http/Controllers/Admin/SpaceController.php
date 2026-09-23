<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Bascule du personnel vers son propre espace participant. Identité unifiée
 * (option B) : un membre du staff est aussi un utilisateur qui peut participer,
 * communiquer, s'inscrire. On l'authentifie (en plus) sur le guard « account »
 * - la session back-office reste active, un lien « Retour au back-office »
 * permet de revenir.
 */
class SpaceController extends Controller
{
    public function participant(): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user !== null && ! Auth::guard('account')->check()) {
            Auth::guard('account')->login($user);
        }

        return redirect()->route('account.dashboard');
    }
}
