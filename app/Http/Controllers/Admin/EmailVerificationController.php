<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Confirmation d'une adresse e-mail de staff via le lien signé (StaffVerifyEmail).
 * Marque l'utilisateur vérifié puis redirige vers la connexion admin.
 */
class EmailVerificationController extends Controller
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals($hash, sha1($user->getEmailForVerification())), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('admin.login')->with('ukc_status', 'Adresse e-mail vérifiée. Vous pouvez vous connecter.');
    }
}
