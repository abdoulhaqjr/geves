<?php

namespace App\Livewire\Account;

use App\Models\Edition;
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
        return view('livewire.account.dashboard', [
            'account' => Auth::guard('account')->user(),
            'openEdition' => Edition::where('status', 'open')
                ->where('submission_open', true)
                ->latest('id')
                ->first(),
        ])->layout('components.layouts.participant', ['title' => 'Tableau de bord']);
    }
}
