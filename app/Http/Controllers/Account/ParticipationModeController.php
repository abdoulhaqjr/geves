<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Choix de présence d'un participant pour un événement donné. */
class ParticipationModeController extends Controller
{
    public function __invoke(Request $request, Event $event): RedirectResponse
    {
        $account = Auth::guard('account')->user();
        $participant = Participant::query()->where('event_id', $event->id)
            ->where('user_id', $account->id)->firstOrFail();
        $modes = (new SubmissionFormConfig($event))->participationModes();
        $data = $request->validate([
            'participation_mode' => ['required', Rule::in(array_keys($modes))],
        ]);

        $participant->update(['participation_mode' => $data['participation_mode']]);

        return redirect()->to(route('account.payment', $event).'#mode-participation')
            ->with('ukc_status', 'Mode de participation au colloque enregistré. Le mode de présentation de vos communications reste inchangé.');
    }
}
