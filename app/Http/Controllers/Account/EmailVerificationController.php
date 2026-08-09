<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Confirme l'adresse e-mail d'un compte participant depuis le lien signé envoyé
 * par la notification VerifyEmail (URL réécrite dans AppServiceProvider).
 */
class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        // Le lien doit correspondre au compte connecté.
        if (! $account || (string) $account->getKey() !== $id) {
            abort(403);
        }

        if (! hash_equals($hash, sha1($account->getEmailForVerification()))) {
            abort(403);
        }

        if ($account->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        if ($account->markEmailAsVerified()) {
            event(new Verified($account));
        }

        return redirect()->route('account.dashboard')
            ->with('ukc_status', 'Votre adresse e-mail est confirmée. Vous pouvez maintenant soumettre une communication.');
    }
}
