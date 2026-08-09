<?php

namespace App\Livewire\Admin;

use App\Models\Colloque;
use App\Models\Edition;
use App\Models\Submission;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tableau de bord du back-office : tuiles de synthèse.
 */
#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'colloques' => Colloque::count(),
            'editions' => Edition::count(),
            'openEditions' => Edition::where('status', 'open')->count(),
            'submissions' => Submission::count(),
        ])->layout('components.layouts.admin', ['title' => 'Tableau de bord']);
    }
}
