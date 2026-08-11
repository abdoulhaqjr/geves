<?php

namespace App\Livewire\Account;

use App\Models\Account;
use App\Support\Totp;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Connexion d'un compte participant (guard « account »), avec limitation du
 * nombre de tentatives. Si le compte a activé la double authentification (TOTP),
 * la connexion n'est finalisée qu'après un second facteur (code de l'app ou
 * code de récupération) — l'identifiant reste en session en attendant.
 */
#[Layout('components.layouts.auth')]
#[Title('Connexion')]
class Login extends Component
{
    private const SESSION_LOGIN_ID = 'account.2fa.login_id';

    private const SESSION_LOGIN_REMEMBER = 'account.2fa.login_remember';

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /** Le second facteur est requis (le mot de passe a été validé). */
    public bool $twoFactor = false;

    /** Code du second facteur (TOTP à 6 chiffres ou code de récupération). */
    public string $code = '';

    public function authenticate(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $account = Account::where('email', $this->email)->first();

        if (! $account || ! Hash::check($this->password, $account->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('Ces identifiants ne correspondent à aucun compte.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Second facteur requis : ne pas connecter tout de suite.
        if ($account->hasTotpEnabled()) {
            session()->put(self::SESSION_LOGIN_ID, $account->id);
            session()->put(self::SESSION_LOGIN_REMEMBER, $this->remember);
            $this->twoFactor = true;
            $this->code = '';

            return;
        }

        $this->completeLogin($account, $this->remember);
    }

    /** Vérifie le second facteur puis finalise la connexion. */
    public function confirmChallenge(): void
    {
        $this->validate(['code' => ['required', 'string']]);

        $id = session(self::SESSION_LOGIN_ID);
        $account = $id ? Account::find($id) : null;

        if (! $account) {
            $this->resetChallenge();

            throw ValidationException::withMessages([
                'code' => __('Session expirée. Reprenez la connexion.'),
            ]);
        }

        $code = trim($this->code);

        // TOTP d'abord, puis repli sur un code de récupération à usage unique.
        if (Totp::verify((string) $account->two_factor_secret, $code)) {
            // OK
        } elseif ($account->consumeRecoveryCode($code)) {
            $account->save();
        } else {
            throw ValidationException::withMessages([
                'code' => __('Code de vérification invalide.'),
            ]);
        }

        $remember = (bool) session(self::SESSION_LOGIN_REMEMBER, false);
        $this->resetChallenge();
        $this->completeLogin($account, $remember);
    }

    /** Repart de l'écran d'identifiants. */
    public function backToLogin(): void
    {
        $this->resetChallenge();
        $this->reset('password', 'code');
    }

    private function resetChallenge(): void
    {
        session()->forget([self::SESSION_LOGIN_ID, self::SESSION_LOGIN_REMEMBER]);
        $this->twoFactor = false;
    }

    private function completeLogin(Account $account, bool $remember): void
    {
        Auth::guard('account')->login($account, $remember);
        session()->regenerate();
        session()->put('account.2fa.passed', true);

        $this->redirectIntended(route('account.dashboard'), navigate: true);
    }

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Trop de tentatives. Réessayez dans :sec secondes.', ['sec' => $seconds]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.account.login');
    }
}
