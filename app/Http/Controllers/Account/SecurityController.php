<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Sms\SmsManager;
use App\Support\Totp;
use App\Support\UserAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Sécurité du compte participant (contrôleur + Blade, sans Livewire) :
 * changement de mot de passe, double authentification par application (TOTP) ou
 * par SMS, codes de récupération, et gestion des sessions actives. Le secret
 * provisoire d'activation et l'état des assistants vivent en session ; les codes
 * de récupération ne s'affichent qu'une fois (flash). Sémantique alignée sur le
 * pattern bizzcart.
 */
class SecurityController extends Controller
{
    private const PENDING_SECRET = 'account.2fa.pending_secret';

    private const SMS_SETUP = 'account.2fa.sms_setup';

    private const SMS_SENT = 'account.2fa.sms_code_sent';

    public function index(): View
    {
        $account = Auth::guard('account')->user();
        $secret = (string) session(self::PENDING_SECRET, '');

        return view('account.security', [
            'account' => $account,
            'enabled' => $account->hasTwoFactorEnabled(),
            'method' => $account->twoFactorMethod(),
            'recoveryCount' => $account->countRecoveryCodes(),
            'showingSetup' => $secret !== '',
            'showingSmsSetup' => (bool) session(self::SMS_SETUP, false),
            'smsCodeSent' => (bool) session(self::SMS_SENT, false),
            'phone' => old('phone', (string) $account->phone),
            'recoveryCodes' => (array) session('recovery_codes', []),
            'setupSecret' => $secret,
            'setupSecretFormatted' => $secret !== '' ? trim(chunk_split($secret, 4, ' ')) : '',
            'setupQr' => $secret !== '' ? Totp::qrCodeSvg($secret, (string) $account->email) : '',
            'sessions' => $this->activeSessions(),
        ]);
    }

    /** Change le mot de passe (le mot de passe actuel est exigé). */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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

        return back()->with('ukc_status', 'Mot de passe mis à jour.');
    }

    /** Étape 1 TOTP : génère un secret provisoire et affiche le QR. */
    public function startSetup(): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        if (! $account->hasTotpEnabled()) {
            session()->put(self::PENDING_SECRET, Totp::generateSecret());
            session()->forget([self::SMS_SETUP, self::SMS_SENT]); // exclusif du flux SMS
        }

        return back();
    }

    public function cancelSetup(): RedirectResponse
    {
        session()->forget(self::PENDING_SECRET);

        return back();
    }

    /** Étape 2 TOTP : vérifie le code puis active la 2FA + génère les codes de secours. */
    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $secret = (string) session(self::PENDING_SECRET);

        if ($secret === '') {
            throw ValidationException::withMessages([
                'code' => __('La configuration a expiré. Recommencez l’activation.'),
            ]);
        }

        if (! Totp::verify($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => __('Code invalide. Vérifiez l’heure de votre téléphone et réessayez.'),
            ]);
        }

        $account = Auth::guard('account')->user();
        $account->two_factor_secret = $secret;
        $account->two_factor_confirmed_at = now();
        $codes = $account->generateRecoveryCodes();
        $account->save();

        session()->forget(self::PENDING_SECRET);
        // La session courante est déjà authentifiée : ne pas re-challenger.
        session()->put('account.2fa.passed', true);

        return back()->with('recovery_codes', $codes)->with('ukc_status', 'Double authentification activée.');
    }

    /** Démarre l'activation 2FA par SMS (saisie du numéro). */
    public function startSmsSetup(): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        if (! $account->hasTwoFactorEnabled()) {
            session()->put(self::SMS_SETUP, true);
            session()->forget([self::SMS_SENT, self::PENDING_SECRET]); // exclusif du flux TOTP
        }

        return back();
    }

    /** Envoie un code de vérification au numéro saisi. */
    public function sendSmsSetupCode(Request $request, SmsManager $sms): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ().-]{8,20}$/'],
        ], attributes: ['phone' => 'numéro de téléphone']);

        $account = Auth::guard('account')->user();
        $code = $account->generateOtp();
        $account->phone = (string) $request->input('phone');
        $account->save();

        try {
            $sms->send((string) $account->phone, 'GEVES — code de vérification : '.$code.'.');
        } catch (\Throwable $e) {
            report($e);
        }

        session()->put(self::SMS_SETUP, true);
        session()->put(self::SMS_SENT, true);

        return back()->with('ukc_status', 'Code envoyé par SMS.');
    }

    /** Vérifie le code SMS puis active la 2FA par SMS + codes de secours. */
    public function confirmSmsSetup(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $account = Auth::guard('account')->user();

        if (! $account->verifyOtp(trim((string) $request->input('code')))) {
            throw ValidationException::withMessages([
                'code' => __('Code invalide ou expiré. Renvoyez un code et réessayez.'),
            ]);
        }

        $account->disableTotp();          // repart d'un état propre (méthode exclusive)
        $account->two_factor_method = 'sms';
        $account->two_factor_confirmed_at = now();
        $codes = $account->generateRecoveryCodes();
        $account->save();

        session()->put('account.2fa.passed', true);
        session()->forget([self::SMS_SETUP, self::SMS_SENT]);

        return back()->with('recovery_codes', $codes)->with('ukc_status', 'Double authentification par SMS activée.');
    }

    public function cancelSmsSetup(): RedirectResponse
    {
        session()->forget([self::SMS_SETUP, self::SMS_SENT]);

        return back();
    }

    /** Régénère les codes de secours (confirmation par mot de passe). */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        if (! $account->hasTotpEnabled() && ! $account->hasTwoFactorEnabled()) {
            return back();
        }

        $this->requirePassword($request, $account);

        $codes = $account->generateRecoveryCodes();
        $account->save();

        return back()->with('recovery_codes', $codes)->with('ukc_status', 'Nouveaux codes de récupération générés.');
    }

    /** Désactive la 2FA (confirmation par mot de passe). */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        $this->requirePassword($request, $account);

        $account->disableTotp();
        $account->save();

        session()->forget('account.2fa.passed');

        return back()->with('ukc_status', 'Double authentification désactivée.');
    }

    /** Révoque une autre session du compte (déconnecte l'appareil concerné). */
    public function revokeSession(string $id): RedirectResponse
    {
        if ($id !== session()->getId()) {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', Auth::guard('account')->id())
                ->where('id', $id)
                ->delete();
        }

        return back()->with('ukc_status', 'Session révoquée.');
    }

    /** Déconnecte toutes les autres sessions (confirmation par mot de passe). */
    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        $this->requirePassword($request, $account);

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $account->getAuthIdentifier())
            ->where('id', '!=', session()->getId())
            ->delete();

        return back()->with('ukc_status', 'Toutes les autres sessions ont été déconnectées.');
    }

    /** Valide le champ `disable_password` contre le mot de passe du compte. */
    private function requirePassword(Request $request, object $account): void
    {
        $request->validate(['disable_password' => ['required', 'string']], attributes: [
            'disable_password' => 'mot de passe',
        ]);

        if (! Hash::check((string) $request->input('disable_password'), $account->password)) {
            throw ValidationException::withMessages([
                'disable_password' => __('Le mot de passe est incorrect.'),
            ]);
        }
    }

    /**
     * Sessions actives du compte (driver « database »), la plus récente d'abord.
     *
     * @return list<array{id: string, current: bool, device: string, ip: string, last: ?string}>
     */
    private function activeSessions(): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $accountId = Auth::guard('account')->id();
        $currentId = session()->getId();

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $accountId)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($s) => [
                'id' => (string) $s->id,
                'current' => (string) $s->id === $currentId,
                'device' => UserAgent::describe($s->user_agent),
                'ip' => (string) ($s->ip_address ?: '—'),
                'last' => $s->last_activity ? Carbon::createFromTimestamp($s->last_activity)->diffForHumans() : null,
            ])
            ->all();
    }
}
