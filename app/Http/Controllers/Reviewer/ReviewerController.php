<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Review;
use App\Models\ReviewerBid;
use App\Models\Submission;
use App\Support\ReviewGrid;
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

        // Appel à candidatures : soumissions de l'édition du relecteur, en aveugle,
        // encore ouvertes et non déjà confiées à ce relecteur.
        $biddable = Submission::where('edition_id', $reviewer->edition_id)
            ->where('revision_of', 0)
            ->whereIn('status', ['submitted', 'under_review'])
            ->whereDoesntHave('reviews', fn ($q) => $q->where('reviewer_id', $reviewer->id))
            ->orderBy('id')
            ->get(['id', 'ref_code', 'title', 'abstract', 'track']);

        $bids = ReviewerBid::where('reviewer_id', $reviewer->id)
            ->get()->keyBy('submission_id');

        return view('reviewer.portal', [
            'reviewer' => $reviewer,
            'reviews' => Review::where('reviewer_id', $reviewer->id)
                ->with('submission.edition')->latest('id')->get(),
            'biddable' => $biddable,
            'bids' => $bids,
            'interests' => ReviewerBid::INTERESTS,
        ]);
    }

    /** Enregistre les candidatures (préférences + conflits) du relecteur. */
    public function saveBids(Request $request, string $token): RedirectResponse
    {
        $reviewer = $this->reviewer($token);

        $data = $request->validate([
            'bids' => ['array'],
            'bids.*.interest' => ['nullable', 'in:'.implode(',', array_keys(ReviewerBid::INTERESTS))],
            'bids.*.conflict' => ['nullable', 'boolean'],
        ]);

        // N'accepte que les soumissions de l'édition du relecteur (garde-fou).
        $allowed = Submission::where('edition_id', $reviewer->edition_id)
            ->where('revision_of', 0)->pluck('id')->flip();

        foreach ($data['bids'] ?? [] as $submissionId => $bid) {
            if (! $allowed->has((int) $submissionId)) {
                continue;
            }
            ReviewerBid::updateOrCreate(
                ['submission_id' => (int) $submissionId, 'reviewer_id' => $reviewer->id],
                [
                    'interest' => $bid['interest'] ?? ReviewerBid::NEUTRAL,
                    'conflict' => (bool) ($bid['conflict'] ?? false),
                ],
            );
        }

        return back()->with('ukc_status', 'Vos préférences ont été enregistrées. Merci !');
    }

    public function evaluate(string $token, Review $review): View
    {
        $reviewer = $this->reviewer($token);
        abort_unless((int) $review->reviewer_id === (int) $reviewer->id, 404);

        $review->load('submission.edition');

        return view('reviewer.evaluate', [
            'reviewer' => $reviewer,
            'review' => $review,
            'recommendations' => Review::RECOMMENDATIONS,
            'criteria' => ReviewGrid::criteria($review->submission?->edition),
            'scale' => ReviewGrid::SCALE,
        ]);
    }

    public function submitEvaluation(Request $request, string $token, Review $review): RedirectResponse
    {
        $reviewer = $this->reviewer($token);
        abort_unless((int) $review->reviewer_id === (int) $reviewer->id, 404);

        $review->load('submission.edition');
        $criteria = ReviewGrid::criteria($review->submission?->edition);

        $rules = [
            'recommendation' => ['required', 'in:'.implode(',', array_keys(Review::RECOMMENDATIONS))],
            'comments' => ['nullable', 'string', 'max:5000'],
            'comments_private' => ['nullable', 'string', 'max:5000'],
        ];
        foreach ($criteria as $c) {
            $rules['scores.'.$c['key']] = ['nullable', 'integer', 'min:0', 'max:'.ReviewGrid::SCALE];
        }
        $data = $request->validate($rules);

        $scores = ReviewGrid::sanitizeScores((array) $request->input('scores', []), $criteria);
        $overall = ReviewGrid::overall($scores, $criteria);

        $review->update([
            'recommendation' => $data['recommendation'],
            'scores' => $scores ?: null,
            'score' => $overall,
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
        abort_unless($reviewer !== null, 404);

        return $reviewer;
    }
}
