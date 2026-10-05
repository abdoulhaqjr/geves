<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\CurrentEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bascule du colloque courant dans l'espace participant. Le compte peut participer
 * à plusieurs colloques : cette action mémorise en session l'événement choisie (si le
 * compte y participe), puis renvoie à la page précédente.
 */
class EventContextController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $account = Auth::guard('account')->user();

        $data = $request->validate([
            'event_id' => ['required', 'integer'],
        ]);

        if (CurrentEvent::switchTo($account, (int) $data['event_id'])) {
            $event = CurrentEvent::current($account);
            session()->flash('ukc_status', 'Événement courant : '.($event?->title ?: 'mis à jour').'.');
        }

        return redirect()->route('account.dashboard');
    }
}
