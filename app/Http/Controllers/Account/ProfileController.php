<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\HalClient;
use App\Support\Orcid;
use App\Support\OrcidClient;
use App\Support\ParticipantTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Édition du profil du compte participant (contrôleur + Blade, sans Livewire).
 * L'affiliation est requise pour pouvoir soumettre (cf. User::canSubmit()).
 * Autocomplétion des affiliations via HAL/AuréHAL (endpoint JSON) quand le
 * réglage est activé.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('account.profile', [
            'account' => Auth::guard('account')->user(),
            'civilities' => ['M.', 'Mme', 'Pr', 'Dr'],
            'participantTypes' => ParticipantTypes::all(),
            'halEnabled' => HalClient::enabled(),
            'orcidConnect' => app(OrcidClient::class)->isConfigured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'civility' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['required', 'string', 'max:150'],
            'grade' => ['nullable', 'string', 'max:100'],
            // Obligatoire : identifie la personne (rapprochement auteurs/formateurs)
            // et pré-remplit la page de la passerelle de paiement (mobile money).
            'phone' => ['required', 'string', 'max:50'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'orcid' => ['nullable', 'string', 'max:40', function ($attr, $value, $fail) {
                if (filled($value) && ! Orcid::isValid((string) $value)) {
                    $fail('L’identifiant ORCID est invalide (format 0000-0002-1825-0097).');
                }
            }],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Normalise l'ORCID en forme canonique (ou null si vide).
        $data['orcid'] = filled($data['orcid'] ?? null) ? Orcid::normalize((string) $data['orcid']) : null;

        $account = Auth::guard('account')->user();

        // Une modification manuelle de l'ORCID annule la vérification OAuth.
        if (($data['orcid'] ?? null) !== $account->orcid) {
            $account->orcid_verified_at = null;
        }

        // Photo de profil : suppression explicite ou remplacement.
        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($account->photo_path);
            $account->photo_path = null;
        } elseif ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('account-photos', 'public');
            $this->deletePhoto($account->photo_path);
            $account->photo_path = $path;
        }

        $account->fill(collect($data)->except('photo')->all());
        $account->save();

        return back()->with('ukc_status', 'Profil enregistré.');
    }

    private function deletePhoto(?string $path): void
    {
        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Autocomplétion HAL de l'affiliation (endpoint JSON pour l'Alpine). */
    public function affiliations(Request $request): JsonResponse
    {
        if (! HalClient::enabled()) {
            return response()->json([]);
        }

        return response()->json(HalClient::searchStructures((string) $request->query('q', '')));
    }
}
