<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\Orcid;
use App\Support\OrcidClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Connexion ORCID (OAuth 3-legged, scope /authenticate) pour le compte
 * participant : prouve l'appartenance de l'identifiant ORCID. Le protocole state
 * protège du CSRF ; l'iD authentifié est enregistré et marqué vérifié.
 */
class OrcidController extends Controller
{
    public function connect(Request $request, OrcidClient $orcid): RedirectResponse
    {
        if (! $orcid->isConfigured()) {
            return redirect()->route('account.profile')->with('ukc_error', 'La connexion ORCID n’est pas configurée.');
        }

        $state = Str::random(40);
        $request->session()->put('orcid_oauth_state', $state);

        return redirect()->away($orcid->authorizeUrl(route('account.orcid.callback'), $state));
    }

    public function callback(Request $request, OrcidClient $orcid): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()->route('account.profile')->with('ukc_error', 'Connexion ORCID annulée.');
        }

        $expected = (string) $request->session()->pull('orcid_oauth_state', '');
        $state = (string) $request->query('state', '');
        if ($expected === '' || ! hash_equals($expected, $state)) {
            return redirect()->route('account.profile')->with('ukc_error', 'Session ORCID invalide, veuillez réessayer.');
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return redirect()->route('account.profile')->with('ukc_error', 'Code d’autorisation ORCID manquant.');
        }

        try {
            $data = $orcid->exchange($code, route('account.orcid.callback'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('account.profile')->with('ukc_error', 'Échec de la connexion ORCID. Réessayez plus tard.');
        }

        $canonical = Orcid::normalize($data['orcid']);
        if ($canonical === null) {
            return redirect()->route('account.profile')->with('ukc_error', 'ORCID renvoyé invalide.');
        }

        $account = Auth::guard('account')->user();
        $account->orcid = $canonical;
        $account->orcid_verified_at = now();
        $account->save();

        return redirect()->route('account.profile')->with('ukc_status', 'ORCID vérifié et connecté ('.$canonical.').');
    }
}
