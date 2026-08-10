<?php

namespace App\Livewire\Account;

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tableau de bord du participant : parcours (stepper), prochaine étape, et blocs
 * personnels — communications, paiements & reçus, attestations.
 */
#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render()
    {
        $account = Auth::guard('account')->user();

        $participantIds = Participant::where('account_id', $account->id)->pluck('id');

        // Édition où une communication est acceptée et les frais non encore réglés.
        $payable = Participant::whereIn('id', $participantIds)
            ->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->with('edition')
            ->first();

        return view('livewire.account.dashboard', [
            'account' => $account,
            'openEdition' => Edition::where('status', 'open')
                ->where('submission_open', true)
                ->latest('id')
                ->first(),
            'payableEdition' => $payable?->edition,
            'submissions' => Submission::whereIn('participant_id', $participantIds)
                ->where('revision_of', 0)->with('edition')->latest('id')->limit(5)->get(),
            'payments' => Payment::whereIn('participant_id', $participantIds)
                ->where('status', 'paid')->with('edition')->latest('id')->get(),
            'attestations' => Attestation::whereIn('participant_id', $participantIds)
                ->where('revoked', false)->with('edition')->latest('id')->get(),
        ])->layout('components.layouts.participant', ['title' => 'Tableau de bord']);
    }
}
