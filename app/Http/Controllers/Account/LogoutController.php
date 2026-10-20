<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Déconnexion d'un compte participant (guard « account »).
 */
class LogoutController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // ⚠️ En IMPERSONATION, « se déconnecter » ne doit PAS vider la session : le
        // staff et le compte impersonifié partagent la même session, et l'invalider
        // déconnecterait aussi l'admin de SA propre session. On quitte seulement
        // l'impersonation et on rend la main au staff. Filet de sécurité couvrant
        // tous les chemins de déconnexion, pas seulement le bouton de la barre.
        if (app('impersonate')->isImpersonating()) {
            Auth::guard('account')->user()?->leaveImpersonation();

            return redirect()->route('admin.users.index')
                ->with('ukc_status', 'Impersonation terminée.');
        }

        Auth::guard('account')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('account.login');
    }
}
