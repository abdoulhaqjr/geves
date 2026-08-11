<?php

namespace App\Livewire\Account;

use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * « Mes documents » : espace consolidé regroupant les documents personnels du
 * participant — attestations vérifiables, reçus de paiement, et documents des
 * éditions auxquelles il participe (ressources + livret des résumés HTML/.docx).
 */
#[Title('Mes documents')]
class Documents extends Component
{
    public function render()
    {
        $account = Auth::guard('account')->user();

        $participantIds = Participant::where('account_id', $account->id)->pluck('id');

        $editionIds = Participant::where('account_id', $account->id)
            ->pluck('edition_id')->unique()->filter();

        $editionDocuments = Edition::whereIn('id', $editionIds)
            ->with(['media' => fn ($q) => $q->orderBy('ordre')->orderBy('id')])
            ->latest('id')
            ->get()
            ->map(fn (Edition $e) => [
                'edition' => $e,
                'media' => $e->media,
                'hasBooklet' => $e->submissions()
                    ->where('revision_of', 0)->where('status', 'accepted')->exists(),
            ])
            ->filter(fn ($d) => $d['media']->isNotEmpty() || $d['hasBooklet'])
            ->values();

        return view('livewire.account.documents', [
            'payments' => Payment::whereIn('participant_id', $participantIds)
                ->where('status', 'paid')->with('edition')->latest('id')->get(),
            'attestations' => Attestation::whereIn('participant_id', $participantIds)
                ->where('revoked', false)->with('edition')->latest('id')->get(),
            'editionDocuments' => $editionDocuments,
        ])->layout('components.layouts.participant', ['title' => 'Mes documents']);
    }
}
