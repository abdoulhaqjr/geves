<?php

namespace App\Livewire\Reviewer;

use App\Models\Participant;
use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Évaluation d'une communication par un relecteur (accès par jeton, en aveugle).
 */
#[Layout('components.layouts.app')]
#[Title('Évaluer une communication')]
class Evaluate extends Component
{
    public Participant $reviewer;

    public Review $review;

    public string $recommendation = '';

    public ?string $score = null;

    public string $comments = '';

    public string $comments_private = '';

    public bool $saved = false;

    public function mount(string $token, Review $review): void
    {
        $reviewer = Participant::where('access_token', $token)
            ->where('role', 'reviewer')
            ->first();

        abort_unless($reviewer && (int) $review->reviewer_id === (int) $reviewer->id, 404);

        $this->reviewer = $reviewer;
        $this->review = $review->load('submission.edition');

        $this->recommendation = (string) $review->recommendation;
        $this->score = $review->score !== null ? (string) $review->score : null;
        $this->comments = (string) $review->comments;
        $this->comments_private = (string) $review->comments_private;
    }

    protected function rules(): array
    {
        return [
            'recommendation' => ['required', 'in:'.implode(',', array_keys(Review::RECOMMENDATIONS))],
            'score' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'comments_private' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function submitEvaluation(): void
    {
        $data = $this->validate();

        $this->review->update([
            'recommendation' => $data['recommendation'],
            'score' => $data['score'] !== null && $data['score'] !== '' ? $data['score'] : null,
            'comments' => $data['comments'] ?: null,
            'comments_private' => $data['comments_private'] ?: null,
            'status' => 'completed',
        ]);

        $this->saved = true;
        session()->flash('ukc_status', 'Votre évaluation a été enregistrée. Merci !');
    }

    public function render()
    {
        return view('livewire.reviewer.evaluate', [
            'recommendations' => Review::RECOMMENDATIONS,
        ]);
    }
}
