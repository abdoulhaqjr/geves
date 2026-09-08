<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Changement de langue (i18n) : mémorise le choix en session et, pour un compte
 * participant connecté, l'enregistre comme préférence durable. Renvoie ensuite
 * vers la page précédente.
 */
class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(Locales::supported($locale), 404);

        $request->session()->put('locale', $locale);

        $account = Auth::guard('account')->user();
        if ($account) {
            $account->update(['locale' => $locale]);
        }

        return back();
    }
}
