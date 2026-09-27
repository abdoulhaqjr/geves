<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Review;
use App\Models\ReviewerBid;
use App\Models\Submission;
use App\Support\EditionLock;
use App\Support\Html;
use App\Support\Notifier;
use App\Support\ReviewGrid;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Espace relecteur (côté participant) : « Mes relectures ». Regroupe les
 * relectures confiées à l'utilisateur au titre de ses inscriptions « relecteur »
 * (par édition) et l'évaluation se fait EN PLACE (panneau latéral, sans changer
 * de page), en aveugle. Un appel à candidatures (préférences + conflits) est
 * proposé pour les communications encore ouvertes. Remplace l'ancien portail à
 * jeton public.
 */
class ReviewWorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $reviewerParticipants = $this->reviewerParticipants();
        $editionIds = $reviewerParticipants->pluck('edition_id')->unique()->values();

        $reviews = Review::whereIn('reviewer_id', $reviewerParticipants->pluck('id'))
            ->with('submission.edition')
            ->latest('id')
            ->get();

        // Filtres (même grammaire que les listes du back-office : sélecteurs qui
        // se soumettent au changement + recherche). ⚠️ Ils portent sur la LISTE ;
        // les compteurs, eux, décrivent toujours l'ensemble de mes relectures -
        // sinon « 2 en retard » deviendrait faux dès qu'on filtre.
        $tab = $request->query('tab') === 'candidatures' ? 'candidatures' : 'relectures';
        $status = (string) $request->query('status', '');
        $editionFilter = (string) $request->query('edition_id', '');
        $track = (string) $request->query('track', '');
        $search = trim((string) $request->query('search', ''));

        $editions = $reviewerParticipants->pluck('edition')->filter()->unique('id')->values();
        // Axes réellement présents dans MES relectures : un filtre ne doit pas
        // proposer une valeur qui ne renverrait jamais rien.
        $tracks = $reviews->map(fn (Review $r) => (string) $r->submission?->track)
            ->filter()->unique()->sort()->values();

        $shown = $reviews
            ->when($status !== '', fn ($c) => $c->where('status', $status))
            ->when($editionFilter !== '', fn ($c) => $c->filter(
                fn (Review $r) => (string) $r->submission?->edition_id === $editionFilter
            ))
            ->when($track !== '', fn ($c) => $c->filter(
                fn (Review $r) => (string) $r->submission?->track === $track
            ))
            ->when($search !== '', fn ($c) => $c->filter(function (Review $r) use ($search) {
                $hay = mb_strtolower(trim((string) $r->submission?->ref_code.' '.(string) $r->submission?->title));

                return str_contains($hay, mb_strtolower($search));
            }))
            ->values();

        // Données JSON pour l'évaluation en place (grille par édition incluse).
        $reviewsData = $shown->map(function (Review $r) {
            $sub = $r->submission;
            $criteria = ReviewGrid::criteria($sub?->edition);

            return [
                'id' => (int) $r->id,
                'ref' => $sub?->ref_code,
                'title' => $sub?->title ?: 'Communication',
                'track' => $sub?->track,
                'type' => $sub?->type === 'poster' ? 'Poster' : 'Oral',
                'abstract' => Html::render($sub?->abstract),
                'keywords' => $sub?->keywords,
                'fileUrl' => $sub?->file_url,
                'edition' => $sub?->edition?->title ?: (string) $sub?->edition?->year,
                'completed' => $r->isCompleted(),
                'status' => $r->status,
                'statusLabel' => $r->statusLabel(),
                'statusPill' => $r->statusPill(),
                // L'échéance ne concerne que ce qui reste à rendre : sur une
                // relecture refusée, annulée ou déjà rendue, elle n'a plus de sens.
                'due' => $r->isPending() ? $r->due_at?->translatedFormat('d/m/Y') : null,
                'late' => $r->isLate(),
                'daysLate' => $r->daysLate(),
                'declineReason' => $r->decline_reason,
                'respondUrl' => route('account.reviews.respond', $r),
                // Édition archivée : lecture seule (cf. EditionLock).
                'frozen' => EditionLock::frozen($sub?->edition),
                'recommendation' => $r->recommendation,
                'comments' => $r->comments,
                'comments_private' => $r->comments_private,
                'scores' => (object) $r->criterionScores(),
                'score' => $r->score !== null ? (float) $r->score : null,
                'criteria' => $criteria,
                'submitUrl' => route('account.reviews.submit', $r),
            ];
        })->values();

        // Appel à candidatures : soumissions des éditions du relecteur, en aveugle,
        // encore ouvertes et non déjà confiées à ce relecteur.
        $reviewerIds = $reviewerParticipants->pluck('id');
        $biddable = Submission::whereIn('edition_id', $editionIds)
            // Une édition archivée est close : plus de candidature possible.
            ->whereHas('edition', fn ($q) => $q->where('status', '!=', 'archived'))
            ->where('revision_of', 0)
            ->whereIn('status', ['submitted', 'under_review'])
            ->whereDoesntHave('reviews', fn ($q) => $q->whereIn('reviewer_id', $reviewerIds))
            ->orderBy('id')
            ->get(['id', 'ref_code', 'title', 'abstract', 'track']);

        $bids = ReviewerBid::whereIn('reviewer_id', $reviewerIds)->get()->keyBy('submission_id');

        return view('account.reviews.index', [
            'reviews' => $reviews,
            'reviewsData' => $reviewsData,
            'recommendations' => Review::RECOMMENDATIONS,
            'scale' => ReviewGrid::SCALE,
            // ⚠️ Compteurs DISJOINTS : « à évaluer » ne compte que les relectures
            // acceptées, sinon il englobait les invitations et le total ne
            // tombait plus juste. Chaque carte porte son propre retard.
            'invitedCount' => $reviews->where('status', 'invited')->count(),
            'acceptedCount' => $reviews->where('status', 'accepted')->count(),
            'doneCount' => $reviews->where('status', 'completed')->count(),
            'declinedCount' => $reviews->where('status', 'declined')->count(),
            'cancelledCount' => $reviews->where('status', 'cancelled')->count(),
            'lateInvited' => $reviews->where('status', 'invited')->filter(fn (Review $r) => $r->isLate())->count(),
            'lateAccepted' => $reviews->where('status', 'accepted')->filter(fn (Review $r) => $r->isLate())->count(),
            'tab' => $tab,
            'statuses' => Review::STATUSES,
            'editions' => $editions,
            'status' => $status,
            'edition_id' => $editionFilter,
            'tracks' => $tracks,
            'track' => $track,
            'search' => $search,
            'shownCount' => $shown->count(),
            'biddable' => $biddable,
            'bids' => $bids,
            'interests' => ReviewerBid::INTERESTS,
        ]);
    }

    /**
     * Réponse du relecteur à une invitation : accepter ou refuser (avec motif).
     * Un refus libère la place - la soumission réapparaît dans l'aperçu
     * d'attribution côté admin, sans remplacement automatique : c'est le
     * comité qui décide qui solliciter ensuite.
     */
    public function respond(Request $request, Review $review): RedirectResponse
    {
        $this->authorizeReview($review);
        $review->loadMissing('submission.edition');
        EditionLock::guard($review->submission?->edition);

        $data = $request->validate([
            'answer' => ['required', 'in:accept,decline'],
            'decline_reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Une relecture déjà rendue ne se renégocie pas.
        abort_if($review->isCompleted(), 422);

        if ($data['answer'] === 'accept') {
            $review->update(['status' => 'accepted', 'responded_at' => now(), 'decline_reason' => null]);
            $message = 'Merci, vous avez accepté cette relecture.';
        } else {
            $review->update([
                'status' => 'declined',
                'responded_at' => now(),
                'decline_reason' => ($data['decline_reason'] ?? null) ?: null,
            ]);
            $message = 'Votre refus est enregistré : le comité en est informé.';
        }

        $review->loadMissing('submission', 'reviewer');
        Notifier::staff(
            kind: 'review_response',
            title: $data['answer'] === 'accept' ? 'Relecture acceptée' : 'Relecture refusée',
            body: trim(sprintf(
                '%s a %s la relecture de « %s »%s',
                $review->reviewer?->full_name ?: 'Un relecteur',
                $data['answer'] === 'accept' ? 'accepté' : 'refusé',
                $review->submission?->ref_code ?: ($review->submission?->title ?: 'une communication'),
                $review->decline_reason ? ' - motif : '.$review->decline_reason : '.'
            )),
            url: $review->submission ? route('admin.submissions.show', $review->submission) : null,
            icon: 'sheet',
        );

        return back()->with('ukc_status', $message);
    }

    /** Enregistre l'évaluation en place (AJAX ou repli formulaire classique). */
    public function submit(Request $request, Review $review): JsonResponse|RedirectResponse
    {
        $this->authorizeReview($review);
        EditionLock::guard($review->submission?->edition);
        abort_if(in_array($review->status, ['declined', 'cancelled'], true), 422);
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
            // Évaluer vaut acceptation : une invitation rendue directement
            // ne doit pas rester « à confirmer » côté comité.
            'responded_at' => $review->responded_at ?? now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'review' => [
                    'id' => (int) $review->id,
                    'completed' => true,
                    'recommendation' => $review->recommendation,
                    'score' => $review->score !== null ? (float) $review->score : null,
                    'scores' => (object) $review->criterionScores(),
                    'comments' => $review->comments,
                    'comments_private' => $review->comments_private,
                ],
            ]);
        }

        return back()->with('ukc_status', 'Votre évaluation a été enregistrée. Merci !');
    }

    /** Enregistre les candidatures (préférences + conflits) du relecteur. */
    public function saveBids(Request $request): RedirectResponse
    {
        $reviewerParticipants = $this->reviewerParticipants();
        abort_if($reviewerParticipants->isEmpty(), 403);

        $data = $request->validate([
            'bids' => ['array'],
            'bids.*.interest' => ['nullable', 'in:'.implode(',', array_keys(ReviewerBid::INTERESTS))],
            'bids.*.conflict' => ['nullable', 'boolean'],
        ]);

        // Regroupe (soumission autorisée => inscription relecteur de la bonne édition).
        $editionByReviewer = $reviewerParticipants->pluck('edition_id', 'id');
        $allowed = Submission::whereIn('edition_id', $editionByReviewer->values()->unique())
            ->whereHas('edition', fn ($q) => $q->where('status', '!=', 'archived'))
            ->where('revision_of', 0)
            ->pluck('edition_id', 'id');

        foreach ($data['bids'] ?? [] as $submissionId => $bid) {
            $editionId = $allowed->get((int) $submissionId);
            if ($editionId === null) {
                continue;
            }
            $reviewerId = $reviewerParticipants->firstWhere('edition_id', $editionId)?->id;
            if ($reviewerId === null) {
                continue;
            }
            ReviewerBid::updateOrCreate(
                ['submission_id' => (int) $submissionId, 'reviewer_id' => $reviewerId],
                [
                    'interest' => $bid['interest'] ?? ReviewerBid::NEUTRAL,
                    'conflict' => (bool) ($bid['conflict'] ?? false),
                ],
            );
        }

        return redirect()->route('account.reviews', ['tab' => 'candidatures'])
            ->with('ukc_status', 'Vos préférences ont été enregistrées. Merci !');
    }

    /**
     * Inscriptions « relecteur » de l'utilisateur connecté (une par édition).
     *
     * @return Collection<int, Participant>
     */
    private function reviewerParticipants(): Collection
    {
        $user = Auth::guard('account')->user();

        return Participant::where('user_id', $user->id)
            ->where('role', 'reviewer')
            ->with('edition.colloque')
            ->get();
    }

    /** La relecture appartient-elle bien à une inscription relecteur du compte ? */
    private function authorizeReview(Review $review): void
    {
        abort_unless(
            $this->reviewerParticipants()->pluck('id')->contains((int) $review->reviewer_id),
            403
        );
    }
}
