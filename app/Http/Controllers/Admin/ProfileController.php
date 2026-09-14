<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Orcid;
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
    public function edit(): View
    {
        $staff = Auth::guard('web')->user();

        return view('admin.profile', [
            'staff' => $staff,
            'civilities' => ['M.', 'Mme', 'Pr', 'Dr'],
            'notifPrefs' => $staff?->resolvedNotificationPreferences('staff') ?? [],
        ]);
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

        /** @var \App\Models\User $staff */
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

        /** @var \App\Models\User $staff */
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
