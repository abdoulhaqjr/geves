<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Réinitialisation de mot de passe des comptes participants (broker
 * « accounts »), contrôleur + Blade. Réponse anti-énumération : message neutre
 * de succès quel que soit l'existence de l'e-mail.
 */
class PasswordResetController extends Controller
{
    /** Formulaire « mot de passe oublié ». */
    public function request(): View
    {
        return view('account.auth.forgot-password');
    }

    /** Envoie le lien de réinitialisation (réponse toujours neutre). */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email']]);

        Password::broker('users')->sendResetLink(['email' => $request->input('email')]);

        // Toujours neutre (ne pas révéler si l'e-mail existe).
        return back()->with('password_reset_sent', true);
    }

    /** Formulaire de choix d'un nouveau mot de passe (jeton signé). */
    public function reset(Request $request, string $token): View
    {
        return view('account.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /** Réinitialisation effective. */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('users')->reset(
            $data,
            function ($account) use ($data) {
                $account->forceFill([
                    'password' => $data['password'],
                    'remember_token' => Str::random(60),
                    'must_reset_password' => false,
                ])->save();

                event(new PasswordReset($account));
            }
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('account.login')
            ->with('ukc_status', __('Mot de passe réinitialisé. Vous pouvez vous connecter.'));
    }
}
