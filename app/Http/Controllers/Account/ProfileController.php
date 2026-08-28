<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\HalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Édition du profil du compte participant (contrôleur + Blade, sans Livewire).
 * L'affiliation est requise pour pouvoir soumettre (cf. Account::canSubmit()).
 * Autocomplétion des affiliations via HAL/AuréHAL (endpoint JSON) quand le
 * réglage est activé.
 */
class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('account.profile', [
            'account' => Auth::guard('account')->user(),
            'civilities' => ['M.', 'Mme', 'Dr', 'Pr'],
            'halEnabled' => HalClient::enabled(),
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
            'country' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        Auth::guard('account')->user()->update($data);

        return back()->with('ukc_status', 'Profil enregistré.');
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
