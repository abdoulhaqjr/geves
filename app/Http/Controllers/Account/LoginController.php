<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Sms\SmsManager;
use App\Support\Totp;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connexion d'un compte participant (guard « account »), contrôleur + Blade
 * (sans Livewire), avec limitation du nombre de tentatives. Si le compte a
 * activé la double authentification (TOTP ou OTP SMS), la connexion n'est
 * finalisée qu'après un second facteur ; l'identifiant reste en session en
 * attendant (le challenge est un second POST).
 */
class LoginController extends Controller
{
    private const SESSION_LOGIN_ID = 'account.2fa.login_id';

    private const SESSION_LOGIN_REMEMBER = 'account.2fa.login_remember';

    private const SESSION_METHOD = 'account.2fa.method';

    private const SESSION_PHONE_HINT = 'account.2fa.phone_hint';

    /** Formulaire de connexion (ou écran de second facteur si un challenge est en cours). */
    public function create(): View
    {
        return view('account.auth.login', [
            'twoFactor' => session()->has(self::SESSION_LOGIN_ID),
            'method' => (string) session(self::SESSION_METHOD, ''),
            'phoneHint' => (string) session(self::SESSION_PHONE_HINT, ''),
        ]);
    }

    /** Vérifie les identifiants ; déclenche le second facteur si nécessaire. */
    public function store(Request $request, SmsManager $sms): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);
        $this->ensureIsNotRateLimited($request);

        $account = Account::where('email', $request->input('email'))->first();

        if (! $account || ! Hash::check((string) $request->input('password'), $account->password)) {
            // Porte unique : à défaut d'un compte participant, tenter le personnel
            // de gestion (guard « web ») avec les mêmes identifiants.
            if ($this->attemptStaff($request)) {
                RateLimiter::clear($this->throttleKey($request));

                return redirect()->intended(route('admin.dashboard'));
            }

            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Ces identifiants ne correspondent à aucun compte.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Second facteur requis : ne pas connecter tout de suite.
        if ($account->hasTwoFactorEnabled()) {
            session()->put(self::SESSION_LOGIN_ID, $account->id);
            session()->put(self::SESSION_LOGIN_REMEMBER, $request->boolean('remember'));
            $method = (string) $account->twoFactorMethod();
            session()->put(self::SESSION_METHOD, $method);
            session()->forget(self::SESSION_PHONE_HINT);

            if ($method === 'sms') {
                $this->sendOtp($account, $sms);
            }

            return redirect()->route('account.login');
        }

        return $this->completeLogin($request, $account, $request->boolean('remember'));
    }

    /** Vérifie le second facteur puis finalise la connexion. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $id = session(self::SESSION_LOGIN_ID);
        $account = $id ? Account::find($id) : null;

        if (! $account) {
            $this->resetChallenge();

            throw ValidationException::withMessages([
                'code' => __('Session expirée. Reprenez la connexion.'),
            ]);
        }

        $code = trim((string) $request->input('code'));

        // Vérifie selon la méthode active (OTP SMS ou TOTP), avec repli sur un
        // code de récupération à usage unique dans les deux cas.
        $primaryOk = $account->hasSmsEnabled()
            ? $account->verifyOtp($code)
            : Totp::verify((string) $account->two_factor_secret, $code);

        if ($primaryOk || $account->consumeRecoveryCode($code)) {
            $account->save();
        } else {
            throw ValidationException::withMessages([
                'code' => __('Code de vérification invalide.'),
            ]);
        }

        $remember = (bool) session(self::SESSION_LOGIN_REMEMBER, false);
        $this->resetChallenge();

        return $this->completeLogin($request, $account, $remember);
    }

    /** Renvoie un nouveau code OTP par SMS pendant le challenge. */
    public function resendOtp(SmsManager $sms): RedirectResponse
    {
        $id = session(self::SESSION_LOGIN_ID);
        $account = $id ? Account::find($id) : null;

        if ($account && $account->hasSmsEnabled()) {
            $this->sendOtp($account, $sms);
            session()->flash('ukc_status', 'Un nouveau code a été envoyé.');
        }

        return redirect()->route('account.login');
    }

    /** Abandonne le challenge et repart de l'écran d'identifiants. */
    public function backToLogin(): RedirectResponse
    {
        $this->resetChallenge();

        return redirect()->route('account.login');
    }

    private function sendOtp(Account $account, SmsManager $sms): void
    {
        $code = $account->generateOtp();
        $account->save();
        session()->put(self::SESSION_PHONE_HINT, $this->maskPhone((string) $account->phone));

        try {
            $sms->send((string) $account->phone, 'UK Colloques — votre code de connexion : '.$code.' (valide '.(int) config('sms.otp_ttl', 5).' min).');
        } catch (\Throwable $e) {
            report($e); // en dev (fournisseur « log ») l'envoi ne bloque pas
        }
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        return $digits === '' ? '' : '••• ••• '.mb_substr($digits, -2);
    }

    private function resetChallenge(): void
    {
        session()->forget([
            self::SESSION_LOGIN_ID,
            self::SESSION_LOGIN_REMEMBER,
            self::SESSION_METHOD,
            self::SESSION_PHONE_HINT,
        ]);
    }

    /**
     * Repli « personnel de gestion » : connexion au guard « web » (back-office)
     * quand les identifiants ne correspondent à aucun compte participant. Permet
     * une page de connexion unique pour les participants et le staff.
     */
    private function attemptStaff(Request $request): bool
    {
        if (! Auth::guard('web')->attempt(
            ['email' => $request->input('email'), 'password' => $request->input('password')],
            $request->boolean('remember')
        )) {
            return false;
        }

        $request->session()->regenerate();

        // Activité de connexion (page Utilisateurs) : date + IP.
        Auth::guard('web')->user()?->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return true;
    }

    private function completeLogin(Request $request, Account $account, bool $remember): RedirectResponse
    {
        Auth::guard('account')->login($account, $remember);
        $request->session()->regenerate();
        session()->put('account.2fa.passed', true);

        return redirect()->intended(route('account.dashboard'));
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('Trop de tentatives. Réessayez dans :sec secondes.', ['sec' => $seconds]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }
}
