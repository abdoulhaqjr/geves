<?php

namespace App\Livewire\Account\Submissions;

use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Liste des communications déposées par le compte connecté (toutes éditions).
 */
#[Title('Mes communications')]
class Index extends Component
{
    public function render()
    {
        $account = Auth::guard('account')->user();

        $submissions = Submission::query()
            ->with('edition')
            ->whereHas('participant', fn ($q) => $q->where('account_id', $account->id))
            ->latest('id')
            ->get();

        return view('livewire.account.submissions.index', [
            'submissions' => $submissions,
        ])->layout('components.layouts.participant', ['title' => 'Mes communications']);
    }
}
