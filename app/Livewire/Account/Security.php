<?php

namespace App\Livewire\Account;

use App\Support\Totp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Sécurité du compte participant : changement de mot de passe et double
 * authentification par application (TOTP). Le secret provisoire d'activation est
 * gardé en session (jamais exposé dans l'état Livewire) ; les codes de
 * récupération ne sont affichés qu'une fois, à l'activation ou la régénération.
 * Flux et sémantique alignés sur le pattern bizzcart.
 */
#[Title('Sécurité')]
class Security extends Component
{
    private const SESSION_PENDING_SECRET = 'account.2fa.pending_secret';

    // --- Mot de passe ---
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // --- 2FA ---
    public bool $showingSetup = false;

    public string $code = '';

    /** Codes de récupération affichés une seule fois (activation / régénération). */
    public array $recoveryCodes = [];

    public string $disable_password = '';

    /** Change le mot de passe (le mot de passe actuel est exigé). */
    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $account = Auth::guard('account')->user();

        if (! Hash::check($validated['current_password'], $account->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('Le mot de passe actuel est incorrect.'),
            ]);
        }

        $account->update(['password' => $validated['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');
        session()->flash('ukc_status', 'Mot de passe mis à jour.');
    }

    /** Étape 1 : génère un secret provisoire et affiche le QR code. */
    public function startSetup(): void
    {
        $account = Auth::guard('account')->user();

        if ($account->hasTotpEnabled()) {
            return;
        }

        session()->put(self::SESSION_PENDING_SECRET, Totp::generateSecret());
        $this->recoveryCodes = [];
        $this->code = '';
        $this->showingSetup = true;
    }

    public function cancelSetup(): void
    {
        session()->forget(self::SESSION_PENDING_SECRET);
        $this->reset('showingSetup', 'code');
    }

    /** Étape 2 : vérifie le code puis active la 2FA + génère les codes de secours. */
    public function confirmTwoFactor(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        $secret = (string) session(self::SESSION_PENDING_SECRET);

        if ($secret === '') {
            throw ValidationException::withMessages([
                'code' => __('La configuration a expiré. Recommencez l’activation.'),
            ]);
        }

        if (! Totp::verify($secret, $this->code)) {
            throw ValidationException::withMessages([
                'code' => __('Code invalide. Vérifiez l’heure de votre téléphone et réessayez.'),
            ]);
        }

        $account = Auth::guard('account')->user();
        $account->two_factor_secret = $secret;
        $account->two_factor_confirmed_at = now();
        $codes = $account->generateRecoveryCodes();
        $account->save();

        session()->forget(self::SESSION_PENDING_SECRET);
        // La session courante est déjà authentifiée : ne pas re-challenger.
        session()->put('account.2fa.passed', true);

        $this->recoveryCodes = $codes;
        $this->reset('showingSetup', 'code');
        session()->flash('ukc_status', 'Double authentification activée.');
    }

    /** Régénère les codes de secours (confirmation par mot de passe). */
    public function regenerateRecoveryCodes(): void
    {
        $account = Auth::guard('account')->user();

        if (! $account->hasTotpEnabled()) {
            return;
        }

        $this->validate(['disable_password' => ['required', 'string']], attributes: [
            'disable_password' => 'mot de passe',
        ]);

        if (! Hash::check($this->disable_password, $account->password)) {
            throw ValidationException::withMessages([
                'disable_password' => __('Le mot de passe est incorrect.'),
            ]);
        }

        $codes = $account->generateRecoveryCodes();
        $account->save();

        $this->reset('disable_password');
        $this->recoveryCodes = $codes;
        session()->flash('ukc_status', 'Nouveaux codes de récupération générés.');
    }

    /** Désactive la 2FA (confirmation par mot de passe). */
    public function disableTwoFactor(): void
    {
        $account = Auth::guard('account')->user();

        $this->validate(['disable_password' => ['required', 'string']], attributes: [
            'disable_password' => 'mot de passe',
        ]);

        if (! Hash::check($this->disable_password, $account->password)) {
            throw ValidationException::withMessages([
                'disable_password' => __('Le mot de passe est incorrect.'),
            ]);
        }

        $account->disableTotp();
        $account->save();

        session()->forget('account.2fa.passed');
        $this->reset('disable_password', 'recoveryCodes');
        session()->flash('ukc_status', 'Double authentification désactivée.');
    }

    /** Secret provisoire en cours d'activation (lecture depuis la session). */
    public function pendingSecret(): string
    {
        return (string) session(self::SESSION_PENDING_SECRET, '');
    }

    public function render()
    {
        $account = Auth::guard('account')->user();
        $secret = $this->pendingSecret();

        return view('livewire.account.security', [
            'account' => $account,
            'enabled' => $account->hasTotpEnabled(),
            'recoveryCount' => $account->countRecoveryCodes(),
            'setupSecret' => $secret,
            'setupSecretFormatted' => $secret !== '' ? trim(chunk_split($secret, 4, ' ')) : '',
            'setupQr' => $secret !== '' ? Totp::qrCodeSvg($secret, (string) $account->email) : '',
        ])->layout('components.layouts.participant', ['title' => 'Sécurité']);
    }
}
