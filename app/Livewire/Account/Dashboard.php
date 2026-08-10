<?php

namespace App\Livewire\Account;

use App\Models\Edition;
use App\Models\Participant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tableau de bord du participant (espace personnel, piloté par le compte).
 * Version fondation : accueil + prochaine étape. Les blocs (communications,
 * paiements, documents, attestation) viendront avec leurs modules.
 */
#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render()
    {
        $account = Auth::guard('account')->user();

        // Édition où une communication est acceptée et les frais non encore réglés.
        $payable = Participant::where('account_id', $account->id)
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
        ])->layout('components.layouts.participant', ['title' => 'Tableau de bord']);
    }
}
