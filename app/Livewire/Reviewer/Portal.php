<?php

namespace App\Livewire\Reviewer;

use App\Models\Participant;
use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Portail relecteur (accès par jeton, sans compte) : liste des communications
 * confiées, en aveugle (identité des auteurs masquée).
 */
#[Layout('components.layouts.app')]
#[Title('Portail relecteur')]
class Portal extends Component
{
    public Participant $reviewer;

    public function mount(string $token): void
    {
        $reviewer = Participant::where('access_token', $token)
            ->where('role', 'reviewer')
            ->first();

        abort_unless($reviewer, 404);

        $this->reviewer = $reviewer;
    }

    public function render()
    {
        $reviews = Review::where('reviewer_id', $this->reviewer->id)
            ->with('submission.edition')
            ->latest('id')
            ->get();

        return view('livewire.reviewer.portal', [
            'reviews' => $reviews,
        ]);
    }
}
