<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Écran invitant à confirmer l'adresse e-mail du compte participant, avec renvoi
 * du lien de vérification (contrôleur + Blade). Le lien signé lui-même est traité
 * par {@see EmailVerificationController}.
 */
class VerifyEmailController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        // Déjà vérifié : on file au tableau de bord.
        if (Auth::guard('account')->user()?->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        return view('account.auth.verify-email-notice');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = Auth::guard('account')->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('verification_resent', true);
    }
}
