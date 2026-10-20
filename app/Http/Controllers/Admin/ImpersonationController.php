<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Impersonation d'un compte par le staff (assistance) : « se connecter en tant
 * que ». Cross-guard : le staff (web) devient le compte (account) via
 * lab404/laravel-impersonate. Retour par « quitter ».
 *
 * ⚠️ Le geste part de **Utilisateurs & rôles**, pas des participants : on
 * emprunte un COMPTE, pas une inscription à un événement. La même personne peut
 * d'ailleurs n'avoir aucune fiche participant (modérateur rapproché par e-mail,
 * membre du staff) - elle était alors inatteignable depuis l'ancienne page.
 */
class ImpersonationController extends Controller
{
    /** Démarre l'impersonation du compte. */
    public function take(User $user): RedirectResponse
    {
        $staff = Auth::guard('web')->user();

        if (! $staff?->canImpersonate()) {
            abort(403);
        }

        abort_unless(User::voitLeCompte($staff, $user), 404);
        abort_unless($user->is_active, 403);

        // ⚠️ S'impersonifier soi-même n'emprunte rien : cela déconnecte le staff
        // du back-office pour le reconnecter dans son propre espace participant,
        // et il faut « quitter » pour revenir. Sa ligne est juste là dans la
        // liste - le geste est vite fait par mégarde.
        if ((int) $staff->id === (int) $user->id) {
            return back()->with('ukc_error', 'Vous ne pouvez pas vous connecter en tant que vous-même.');
        }

        $staff->impersonate($user, 'account');

        return redirect()->route('account.dashboard')
            ->with('ukc_status', 'Vous consultez l’espace de '.$user->name.' (impersonation).');
    }

    /** Quitte l'impersonation et rend la main au staff. */
    public function leave(): RedirectResponse
    {
        $manager = app('impersonate');

        if (! $manager->isImpersonating()) {
            return redirect()->route('admin.users.index');
        }

        $account = Auth::guard('account')->user();
        $account?->leaveImpersonation();

        return redirect()->route('admin.users.index')
            ->with('ukc_status', 'Impersonation terminée.');
    }
}
