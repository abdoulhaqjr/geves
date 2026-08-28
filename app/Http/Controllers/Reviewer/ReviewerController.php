<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portail relecteur (contrôleur + Blade), accès par jeton sans compte : liste
 * des communications confiées et formulaire d'évaluation, en aveugle (identité
 * des auteurs masquée).
 */
class ReviewerController extends Controller
{
    public function portal(string $token): View
    {
        $reviewer = $this->reviewer($token);

        return view('reviewer.portal', [
            'reviewer' => $reviewer,
            'reviews' => Review::where('reviewer_id', $reviewer->id)
                ->with('submission.edition')->latest('id')->get(),
        ]);
    }

    public function evaluate(string $token, Review $review): View
    {
        $reviewer = $this->reviewer($token);
        abort_unless((int) $review->reviewer_id === (int) $reviewer->id, 404);

        return view('reviewer.evaluate', [
            'reviewer' => $reviewer,
            'review' => $review->load('submission.edition'),
            'recommendations' => Review::RECOMMENDATIONS,
        ]);
    }

    public function submitEvaluation(Request $request, string $token, Review $review): RedirectResponse
    {
        $reviewer = $this->reviewer($token);
        abort_unless((int) $review->reviewer_id === (int) $reviewer->id, 404);

        $data = $request->validate([
            'recommendation' => ['required', 'in:'.implode(',', array_keys(Review::RECOMMENDATIONS))],
            'score' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'comments_private' => ['nullable', 'string', 'max:5000'],
        ]);

        $review->update([
            'recommendation' => $data['recommendation'],
            'score' => ($data['score'] ?? '') !== '' ? $data['score'] : null,
            'comments' => ($data['comments'] ?? null) ?: null,
            'comments_private' => ($data['comments_private'] ?? null) ?: null,
            'status' => 'completed',
        ]);

        return back()->with('ukc_status', 'Votre évaluation a été enregistrée. Merci !');
    }

    /** Relecteur identifié par son jeton (404 sinon). */
    private function reviewer(string $token): Participant
    {
        $reviewer = Participant::where('access_token', $token)->where('role', 'reviewer')->first();
        abort_unless($reviewer, 404);

        return $reviewer;
    }
}
