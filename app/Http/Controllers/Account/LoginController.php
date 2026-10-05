<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
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

    /** Le login en attente de 2FA doit-il finaliser vers le back-office (staff) ? */
    private const SESSION_IS_STAFF = 'account.2fa.is_staff';

    /** Formulaire de connexion (ou écran de second facteur si un challenge est en cours). */
    public function create(): View|RedirectResponse
    {
        // Déjà connecté au back-office (guard web) : ne pas afficher le formulaire,
        // renvoyer vers l'espace. (Le guard `account` est déjà géré par `guest`.)
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }

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

        $account = User::where('email', $request->input('email'))->first();

        if (! $account || ! Hash::check((string) $request->input('password'), $account->password)) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => __('Ces identifiants ne correspondent à aucun compte.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Réinitialisation forcée (fusion de comptes : deux mots de passe en
        // conflit) : on ne connecte pas, on oriente vers la définition d'un
        // nouveau mot de passe.
        if ($account->must_reset_password) {
            return redirect()->route('account.password.request')
                ->with('ukc_status', __('Pour votre sécurité, définissez un nouveau mot de passe pour continuer.'));
        }

        // Second facteur requis : ne pas connecter tout de suite (staff comme
        // participant). La destination finale (back-office vs espace) est mise en
        // attente jusqu'à la validation du code.
        if ($account->hasTwoFactorEnabled()) {
            session()->put(self::SESSION_LOGIN_ID, $account->id);
            session()->put(self::SESSION_LOGIN_REMEMBER, $request->boolean('remember'));
            session()->put(self::SESSION_IS_STAFF, $this->hasBackOfficeAccess($account));
            $method = (string) $account->twoFactorMethod();
            session()->put(self::SESSION_METHOD, $method);
            session()->forget(self::SESSION_PHONE_HINT);

            if ($method === 'sms') {
                $this->sendOtp($account, $sms);
            }

            return redirect()->route('account.login');
        }

        // Identité unifiée : selon les rôles, on oriente vers le back-office
        // (personnel de gestion) ou l'espace participant. Une seule page, deux
        // destinations.
        if ($this->hasBackOfficeAccess($account)) {
            return $this->completeStaffLogin($request, $account, $request->boolean('remember'));
        }

        return $this->completeLogin($request, $account, $request->boolean('remember'));
    }

    /** Vérifie le second facteur puis finalise la connexion. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $id = session(self::SESSION_LOGIN_ID);
        $account = $id ? User::find($id) : null;

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
        $isStaff = (bool) session(self::SESSION_IS_STAFF, false);
        $this->resetChallenge();

        return $isStaff
            ? $this->completeStaffLogin($request, $account, $remember)
            : $this->completeLogin($request, $account, $remember);
    }

    /** Renvoie un nouveau code OTP par SMS pendant le challenge. */
    public function resendOtp(SmsManager $sms): RedirectResponse
    {
        $id = session(self::SESSION_LOGIN_ID);
        $account = $id ? User::find($id) : null;

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

    private function sendOtp(User $account, SmsManager $sms): void
    {
        $code = $account->generateOtp();
        $account->save();
        session()->put(self::SESSION_PHONE_HINT, $this->maskPhone((string) $account->phone));

        try {
            $sms->send((string) $account->phone, config('app.name').' - votre code de connexion : '.$code.' (valide '.(int) config('sms.otp_ttl', 5).' min).');
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
            self::SESSION_IS_STAFF,
        ]);
    }

    /**
     * L'utilisateur a-t-il un accès back-office (super-admin, admin de colloque
     * ou une fonction d'organisation sur au moins un événement) ? Détermine s'il
     * est orienté vers le back-office plutôt que l'espace participant.
     */
    private function hasBackOfficeAccess(User $user): bool
    {
        return $user->isColloqueSuperAdmin() || $user->canAnyEdition();
    }

    /** Connexion du personnel de gestion : guard « web » + orientation back-office. */
    private function completeStaffLogin(Request $request, User $user, bool $remember = false): RedirectResponse
    {
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        // Activité de connexion (page Utilisateurs) : date + IP.
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function completeLogin(Request $request, User $account, bool $remember): RedirectResponse
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
