<?php

namespace App\Actions;

use App\Mail\ReviewerInvitationMail;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Review;
use App\Models\ReviewerBid;
use App\Models\Submission;
use App\Support\ConflictOfInterest;
use App\Support\Notifier;
use App\Support\ReviewerAssignmentPlanner;
use Illuminate\Support\Facades\Mail;

/**
 * Attribution automatique des relecteurs sur une événement : prépare les données
 * réelles (soumissions à couvrir, relecteurs + expertise + charge, conflits
 * d'intérêts), délègue le choix au {@see ReviewerAssignmentPlanner}, et sait
 * appliquer un plan validé en réutilisant {@see AssignReviewer} (idempotent).
 */
class AutoAssignReviewers
{
    /** Statuts de soumission éligibles à l'attribution. */
    private const OPEN_STATUSES = ['submitted', 'under_review'];

    public function __construct(
        private readonly ReviewerAssignmentPlanner $planner,
        private readonly AssignReviewer $assign,
    ) {}

    /**
     * Construit un aperçu (dry-run) : propositions + charge par relecteur + stats.
     *
     * @return array{
     *   proposals: list<array{submission_id:int, submission_title:string, submission_ref:string, track:string, reviewer_id:int, reviewer_name:string, reason:string, coi:string|null}>,
     *   reviewers: list<array{id:int, name:string, tracks:list<string>, current:int, proposed:int}>,
     *   needing: int,
     *   uncovered: int
     * }
     */
    public function preview(Event $event, int $target, int $maxLoad): array
    {
        $reviewers = Participant::where('event_id', $event->id)
            ->where('role', 'reviewer')->orderBy('last_name')->get();

        $submissions = Submission::where('event_id', $event->id)
            ->where('revision_of', 0)
            ->whereIn('status', self::OPEN_STATUSES)
            ->with('reviews')
            ->orderBy('id')->get();

        // Charge courante par relecteur (relectures actives sur l'événement).
        $load = Review::query()
            ->whereIn('reviewer_id', $reviewers->pluck('id'))
            ->whereIn('status', Review::ACTIVE_STATUSES)
            ->whereHas('submission', fn ($q) => $q->where('event_id', $event->id))
            ->selectRaw('reviewer_id, count(*) as c')->groupBy('reviewer_id')->pluck('c', 'reviewer_id');

        $reviewerDto = $reviewers->map(fn (Participant $r) => [
            'id' => $r->id,
            'tracks' => array_values(array_filter((array) ($r->meta['review_tracks'] ?? []))),
            'load' => (int) ($load[$r->id] ?? 0),
        ])->values()->all();

        // Carte des conflits d'intérêts (niveau le plus élevé par paire).
        $coi = [];
        $subDto = [];
        foreach ($submissions as $sub) {
            // ⚠️ `exclude` reprend TOUS les relecteurs déjà sollicités (y compris
            // ceux qui ont refusé : on ne les represse pas), alors que la
            // couverture ne compte que les relectures ACTIVES - un refus ou une
            // annulation libère la place.
            $assigned = $sub->reviews->pluck('reviewer_id')->filter()->values()->all();
            $active = $sub->reviews->filter(fn (Review $r) => $r->isActive())->count();
            $needed = max(0, $target - $active);
            $subDto[] = ['id' => $sub->id, 'track' => (string) $sub->track, 'needed' => $needed, 'exclude' => $assigned];

            foreach ($reviewers as $r) {
                $coi[$sub->id][$r->id] = ConflictOfInterest::worstLevel(
                    ConflictOfInterest::check($sub, $r->full_name, (string) $r->email, $r->institution)
                );
            }
        }

        // Candidatures des relecteurs (préférences + conflits auto-déclarés).
        $bids = [];
        foreach (ReviewerBid::whereIn('submission_id', $submissions->pluck('id'))->get() as $bid) {
            $bids[$bid->submission_id][$bid->reviewer_id] = [
                'interest' => (string) $bid->interest,
                'conflict' => (bool) $bid->conflict,
            ];
        }

        $plan = $this->planner->plan($subDto, $reviewerDto, $coi, $maxLoad, $bids);

        // Enrichissement pour l'affichage.
        $reviewersById = $reviewers->keyBy('id');
        $subsById = $submissions->keyBy('id');
        $proposedCount = [];
        $proposals = [];
        foreach ($plan as $p) {
            $proposedCount[$p['reviewer_id']] = ($proposedCount[$p['reviewer_id']] ?? 0) + 1;
            $sub = $subsById[$p['submission_id']];
            $rev = $reviewersById[$p['reviewer_id']];
            $proposals[] = [
                'submission_id' => $p['submission_id'],
                'submission_title' => (string) $sub->title,
                'submission_ref' => (string) $sub->ref_code,
                'track' => (string) $sub->track,
                'reviewer_id' => $p['reviewer_id'],
                'reviewer_name' => $rev->full_name,
                'reason' => $p['reason'],
                'coi' => $p['coi'],
            ];
        }

        // Combien de soumissions restent sous-couvertes après le plan ?
        $coveredPerSub = collect($plan)->countBy('submission_id');
        $uncovered = 0;
        foreach ($subDto as $s) {
            if ($s['needed'] > (int) ($coveredPerSub[$s['id']] ?? 0)) {
                $uncovered++;
            }
        }

        return [
            'proposals' => $proposals,
            'reviewers' => $reviewers->map(fn (Participant $r) => [
                'id' => $r->id,
                'name' => $r->full_name,
                'tracks' => array_values(array_filter((array) ($r->meta['review_tracks'] ?? []))),
                'current' => (int) ($load[$r->id] ?? 0),
                'proposed' => (int) ($proposedCount[$r->id] ?? 0),
            ])->values()->all(),
            'needing' => collect($subDto)->where('needed', '>', 0)->count(),
            'uncovered' => $uncovered,
        ];
    }

    /**
     * Applique un plan validé (paires soumission→relecteur), en re-vérifiant
     * l'appartenance à l'événement et l'absence de conflit d'intérêt élevé.
     *
     * @param  list<array{submission_id:int, reviewer_id:int, reason?:string|null}>  $pairs
     * @return int nombre de relectures effectivement créées
     */
    public function apply(Event $event, array $pairs): int
    {
        $created = 0;

        foreach ($pairs as $pair) {
            $submission = Submission::where('event_id', $event->id)->find($pair['submission_id']);
            $reviewer = Participant::where('event_id', $event->id)
                ->where('role', 'reviewer')->find($pair['reviewer_id']);

            if (! $submission || ! $reviewer) {
                continue;
            }

            // Garde-fou : ne jamais appliquer un conflit d'intérêt élevé.
            $level = ConflictOfInterest::worstLevel(
                ConflictOfInterest::check($submission, $reviewer->full_name, (string) $reviewer->email, $reviewer->institution)
            );
            if ($level === ConflictOfInterest::HIGH) {
                continue;
            }

            $review = $this->assign->handle(
                $submission,
                $reviewer->full_name,
                (string) $reviewer->email,
                ($pair['reason'] ?? null) !== null ? (string) $pair['reason'] : null,
            );

            if ($review->wasRecentlyCreated) {
                $created++;
                if (Notifier::emailAllowed($review->reviewer->user, 'review_assigned')) {
                    Mail::to((string) $reviewer->email)->send(new ReviewerInvitationMail($review->reviewer, review: $review));
                }
            }
        }

        return $created;
    }
}
