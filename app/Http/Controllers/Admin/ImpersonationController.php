<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Impersonation d'un compte participant par le staff (assistance) : « se
 * connecter en tant que ». Cross-guard : le staff (web) devient le compte
 * (account) via lab404/laravel-impersonate. Retour par « quitter ».
 */
class ImpersonationController extends Controller
{
    /** Démarre l'impersonation du compte lié au participant. */
    public function take(Participant $participant): RedirectResponse
    {
        $staff = Auth::guard('web')->user();
        $account = $participant->account;

        if (! $staff?->canImpersonate()) {
            abort(403);
        }
        if (! $account) {
            return back()->with('ukc_error', 'Ce participant n’a pas de compte lié.');
        }

        $staff->impersonate($account, 'account');

        return redirect()->route('account.dashboard')
            ->with('ukc_status', 'Vous consultez l’espace de '.$participant->full_name.' (impersonation).');
    }

    /** Quitte l'impersonation et rend la main au staff. */
    public function leave(): RedirectResponse
    {
        $manager = app('impersonate');

        if (! $manager->isImpersonating()) {
            return redirect()->route('admin.participants.index');
        }

        $account = Auth::guard('account')->user();
        $account?->leaveImpersonation();

        return redirect()->route('admin.participants.index')
            ->with('ukc_status', 'Impersonation terminée.');
    }
}
