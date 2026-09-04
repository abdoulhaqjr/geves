<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\CurrentColloque;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bascule du colloque courant dans l'espace participant. Le compte peut participer
 * à plusieurs colloques : cette action mémorise en session l'édition choisie (si le
 * compte y participe), puis renvoie à la page précédente.
 */
class ColloqueContextController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        $data = $request->validate([
            'edition_id' => ['required', 'integer'],
        ]);

        if (CurrentColloque::switchTo($account, (int) $data['edition_id'])) {
            $edition = CurrentColloque::current($account);
            session()->flash('ukc_status', 'Colloque courant : '.($edition?->title ?: 'mis à jour').'.');
        }

        return redirect()->route('account.dashboard');
    }
}
