<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Orcid;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Profil du personnel du back-office (guard « web ») : identité, coordonnées,
 * photo et mot de passe. L'e-mail (identifiant de connexion) et le niveau
 * d'accès restent gérés dans « Utilisateurs ».
 */
class ProfileController extends Controller
{
    /** Secret TOTP provisoire (avant confirmation) - en session. */
    private const PENDING_SECRET = 'admin.2fa.pending_secret';

    public function edit(): View
    {
        $staff = Auth::guard('web')->user();
        $pending = (string) session(self::PENDING_SECRET, '');

        return view('admin.profile', [
            'staff' => $staff,
            'civilities' => ['M.', 'Mme', 'Pr', 'Dr'],
            'notifPrefs' => $staff?->resolvedNotificationPreferences('staff') ?? [],
            'twoFactorEnabled' => $staff?->hasTotpEnabled() ?? false,
            'recoveryCount' => $staff?->countRecoveryCodes() ?? 0,
            'pendingSecret' => $pending,
            'setupQr' => $pending !== '' ? Totp::qrCodeSvg($pending, (string) $staff?->email) : '',
            'setupSecretFormatted' => $pending !== '' ? trim(chunk_split($pending, 4, ' ')) : '',
        ]);
    }

    /** Étape 1 : génère un secret provisoire et affiche le QR. */
    public function startTwoFactor(): RedirectResponse
    {
        session()->put(self::PENDING_SECRET, Totp::generateSecret());

        return redirect()->route('admin.profile')->withFragment('securite');
    }

    public function cancelTwoFactor(): RedirectResponse
    {
        session()->forget(self::PENDING_SECRET);

        return redirect()->route('admin.profile')->withFragment('securite');
    }

    /** Étape 2 : vérifie le code de l'app puis active la 2FA + codes de secours. */
    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $secret = (string) session(self::PENDING_SECRET);

        if ($secret === '') {
            return redirect()->route('admin.profile')->with('ukc_error', 'Reprenez l’activation de la double authentification.');
        }

        if (! Totp::verify($secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => 'Code invalide - vérifiez l’heure de votre téléphone et réessayez.']);
        }

        /** @var User $staff */
        $staff = Auth::guard('web')->user();
        $staff->two_factor_secret = $secret;
        $staff->two_factor_confirmed_at = now();
        $codes = $staff->generateRecoveryCodes();
        $staff->save();

        session()->forget(self::PENDING_SECRET);

        return redirect()->route('admin.profile')->withFragment('securite')
            ->with('ukc_status', 'Double authentification activée.')
            ->with('recovery_codes', $codes);
    }

    public function regenerateRecovery(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);

        /** @var User $staff */
        $staff = Auth::guard('web')->user();
        if (! $staff->hasTotpEnabled()) {
            return back();
        }

        $codes = $staff->generateRecoveryCodes();
        $staff->save();

        return redirect()->route('admin.profile')->withFragment('securite')
            ->with('ukc_status', 'Nouveaux codes de secours générés.')
            ->with('recovery_codes', $codes);
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);

        /** @var User $staff */
        $staff = Auth::guard('web')->user();
        $staff->disableTotp();
        $staff->save();

        return redirect()->route('admin.profile')->withFragment('securite')
            ->with('ukc_status', 'Double authentification désactivée.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'civility' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'orcid' => ['nullable', 'string', 'max:40', function ($attr, $value, $fail) {
                if (filled($value) && ! Orcid::isValid((string) $value)) {
                    $fail('L’identifiant ORCID est invalide (format 0000-0002-1825-0097).');
                }
            }],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['orcid'] = filled($data['orcid'] ?? null) ? Orcid::normalize((string) $data['orcid']) : null;

        /** @var User $staff */
        $staff = Auth::guard('web')->user();

        if (($data['orcid'] ?? null) !== $staff->orcid) {
            $staff->orcid_verified_at = null;
        }

        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($staff->photo_path);
            $staff->photo_path = null;
        } elseif ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('staff-photos', 'public');
            $this->deletePhoto($staff->photo_path);
            $staff->photo_path = $path;
        }

        $staff->fill(collect($data)->except('photo')->all());
        $staff->save();

        return back()->with('ukc_status', 'Profil enregistré.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        /** @var User $staff */
        $staff = Auth::guard('web')->user();
        $staff->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_reset_password' => false,
        ])->save();

        return back()->with('ukc_status', 'Mot de passe mis à jour.');
    }

    private function deletePhoto(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
