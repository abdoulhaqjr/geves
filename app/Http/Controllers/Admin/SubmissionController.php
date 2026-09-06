<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AssignReviewer;
use App\Actions\AutoAssignReviewers;
use App\Events\SubmissionStatusChanged;
use App\Http\Controllers\Controller;
use App\Mail\ReviewerInvitationMail;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Support\ConflictOfInterest;
use App\Support\Notifier;
use App\Support\Plagiarism\PlagiarismRegistry;
use App\Support\SimilarityChecker;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Soumissions du back-office (contrôleur + Blade) : liste filtrable + fiche
 * détaillée avec workflow (mise en relecture, correction, acceptation, refus)
 * notifiant l'auteur, et gestion des relecteurs (assignation, retrait, synthèse,
 * conflits d'intérêts).
 */
class SubmissionController extends Controller
{
    use Concerns\ScopesEditionAccess;

    /** Statuts que l'on peut appliquer depuis la fiche (le dépôt initial exclu). */
    public const ACTIONS = [
        'under_review' => ['Mettre en relecture', 'ghost'],
        'revision' => ['Demander une correction', 'gold'],
        'accepted' => ['Accepter', 'green'],
        'rejected' => ['Refuser', 'danger'],
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $editionId = (string) $request->query('edition_id', '');
        $type = (string) $request->query('type', '');
        $track = (string) $request->query('track', '');
        $scoped = $this->scopedEditionIds($request, 'submissions_list');

        $submissions = Submission::query()
            ->with(['edition', 'participant'])
            ->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($track !== '', fn ($q) => $q->where('track', $track))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($sub) => $sub
                    ->where('title', 'like', $term)
                    ->orWhere('ref_code', 'like', $term)
                    ->orWhereHas('participant', fn ($p) => $p
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Indicateurs par statut (sur l'édition filtrée), indépendants des autres filtres.
        $counts = Submission::query()->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $c = fn (string $s): int => (int) ($counts[$s] ?? 0);

        return view('admin.submissions.index', [
            'submissions' => $submissions,
            'search' => $search,
            'status' => $status,
            'edition_id' => $editionId,
            'type' => $type,
            'track' => $track,
            'editions' => $this->accessibleEditions($request, 'submissions_list'),
            'statuses' => Submission::statuses(),
            'statusPills' => Submission::statusPills(),
            'tracks' => Submission::query()->where('revision_of', 0)
                ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
                ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
                ->whereNotNull('track')->where('track', '!=', '')->distinct()->orderBy('track')->pluck('track'),
            'stats' => [
                'total' => (int) $counts->sum(),
                'underReview' => $c('under_review') + $c('submitted'),
                'accepted' => $c('accepted'),
                'revision' => $c('revision'),
                'rejected' => $c('rejected'),
            ],
        ]);
    }

    /** Export CSV des soumissions filtrées (mêmes filtres que la liste). */
    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $editionId = (string) $request->query('edition_id', '');
        $type = (string) $request->query('type', '');
        $track = (string) $request->query('track', '');
        $scoped = $this->scopedEditionIds($request, 'submissions_list');

        $rows = Submission::query()
            ->with(['edition', 'participant'])
            ->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($track !== '', fn ($q) => $q->where('track', $track))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($sub) => $sub
                    ->where('title', 'like', $term)
                    ->orWhere('ref_code', 'like', $term)
                    ->orWhereHas('participant', fn ($p) => $p
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            })
            ->latest('id')->get();

        $statuses = Submission::statuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Référence', 'Titre', 'Auteur correspondant', 'Type', 'Axe', 'Statut', 'Déposée le', 'Édition']);
            foreach ($rows as $s) {
                fputcsv($out, [
                    $s->ref_code,
                    $s->title,
                    $s->participant?->full_name ?? ($s->authors[0]['name'] ?? ''),
                    $s->type === 'poster' ? 'Poster' : 'Orale',
                    (string) $s->track,
                    $statuses[$s->status] ?? $s->status,
                    $s->created_at?->format('d/m/Y H:i'),
                    $s->edition?->title ?: $s->edition?->year,
                ]);
            }
            fclose($out);
        }, 'soumissions-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function show(Request $request, Submission $submission, SimilarityChecker $similarity, PlagiarismRegistry $plagiarism): View
    {
        $submission->load(['edition', 'participant']);

        $reviews = $submission->reviews()->with('reviewer')->get();
        $completed = $reviews->where('status', 'completed');

        // Similarité interne (gratuite) : autres soumissions les plus proches,
        // restreintes aux éditions accessibles au membre du staff.
        $scoped = $this->scopedEditionIds($request, 'submissions_list');
        $corpus = Submission::query()
            ->whereKeyNot($submission->id)
            ->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->get(['id', 'ref_code', 'title', 'abstract', 'edition_id']);
        $corpusById = $corpus->keyBy('id');
        $similar = collect($similarity->against(
            (string) $submission->abstract,
            $corpus->map(fn ($s) => ['id' => $s->id, 'text' => (string) $s->abstract])->all(),
            0.35,
            5,
        ))->map(fn ($m) => [
            'submission' => $corpusById[$m['id']],
            'score' => $m['score'],
            'percent' => (int) round($m['score'] * 100),
        ])->all();

        $existingReviewers = Participant::where('edition_id', $submission->edition_id)
            ->where('role', 'reviewer')->orderBy('last_name')->get();

        // Détection de conflits d'intérêts (indicative).
        $coiByReview = [];
        foreach ($reviews as $review) {
            $rev = $review->reviewer;
            $coiByReview[$review->id] = $rev
                ? ConflictOfInterest::check($submission, $rev->full_name, (string) $rev->email, $rev->institution)
                : [];
        }
        $coiByReviewer = [];
        foreach ($existingReviewers as $rev) {
            $coiByReviewer[$rev->id] = ConflictOfInterest::worstLevel(
                ConflictOfInterest::check($submission, $rev->full_name, (string) $rev->email, $rev->institution)
            );
        }

        return view('admin.submissions.show', [
            'submission' => $submission,
            'actions' => self::ACTIONS,
            'reviews' => $reviews,
            'recommendations' => Review::RECOMMENDATIONS,
            'averageScore' => $completed->whereNotNull('score')->avg('score'),
            'completedCount' => $completed->count(),
            'timeline' => $this->timeline($submission, $reviews),
            'authorPaid' => $this->authorHasPaid($submission),
            'existingReviewers' => $existingReviewers,
            'coiByReview' => $coiByReview,
            'coiByReviewer' => $coiByReviewer,
            'similar' => $similar,
            'webPlagProvider' => $plagiarism->active(),
            'webPlag' => $submission->meta['web_plagiarism'] ?? null,
        ]);
    }

    /** Lance une vérification anti-plagiat web (à la demande) et met le résultat en cache. */
    public function checkPlagiarism(Submission $submission, PlagiarismRegistry $plagiarism): RedirectResponse
    {
        $provider = $plagiarism->active();
        if ($provider === null) {
            return back()->with('ukc_error', 'Aucun fournisseur anti-plagiat web n’est configuré (Réglages · Intégrations).');
        }

        try {
            $result = $provider->check((string) $submission->abstract);
        } catch (RequestException $e) {
            $status = $e->response->status();
            // Message d'API (Prepostseo : « message » ; Google : « error.message » imbriqué).
            $apiMsg = trim((string) ($e->response->json('message') ?? $e->response->json('error.message') ?? ''));

            return back()->with('ukc_error', match (true) {
                $status === 429 => 'Quota du fournisseur anti-plagiat atteint. Réessayez plus tard.',
                in_array($status, [401, 403], true) => 'Accès '.$provider->label().' refusé'.($apiMsg !== '' ? ' — '.$apiMsg : '').'. Vérifiez la configuration dans Réglages · Intégrations.',
                default => 'Service anti-plagiat indisponible (code '.$status.'). Réessayez plus tard.',
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('ukc_error', 'Vérification anti-plagiat indisponible pour le moment. Réessayez plus tard.');
        }

        $meta = $submission->meta ?? [];
        $meta['web_plagiarism'] = $result;
        $submission->update(['meta' => $meta]);

        return back()->with('ukc_status', 'Vérification anti-plagiat effectuée : '.(int) round($result['plagPercent']).' % de similarité web.');
    }

    public function applyStatus(Request $request, Submission $submission): RedirectResponse
    {
        $status = (string) $request->input('status');
        abort_unless(array_key_exists($status, self::ACTIONS), 400);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:3000']]);

        $submission->update(['status' => $status]);
        $submission->refresh();

        // Effets de bord découplés : notification auteur, invitation co-auteurs…
        SubmissionStatusChanged::dispatch($submission, $status, ($data['note'] ?? null) ?: null);

        return back()->with('ukc_status', 'Statut mis à jour : '.$submission->statusLabel().' — l’auteur a été notifié.');
    }

    public function assignReviewer(Request $request, Submission $submission, AssignReviewer $action): RedirectResponse
    {
        $name = trim((string) $request->input('reviewerName'));
        $email = trim((string) $request->input('reviewerEmail'));

        // Relecteur existant sélectionné : on récupère nom + e-mail.
        if ($request->filled('existingReviewerId')) {
            $reviewer = Participant::find($request->input('existingReviewerId'));
            if (! $reviewer) {
                return back()->withInput()->withErrors(['existingReviewerId' => 'Relecteur introuvable.']);
            }
            $name = $reviewer->full_name;
            $email = (string) $reviewer->email;
        }

        $request->merge(['reviewerName' => $name, 'reviewerEmail' => $email]);
        $request->validate([
            'reviewerName' => ['required', 'string', 'max:200'],
            'reviewerEmail' => ['required', 'email', 'max:200'],
        ]);

        $review = $action->handle($submission, $name, $email);

        // Invitation par e-mail, soumise à la préférence du relecteur.
        if (Notifier::emailAllowed($review->reviewer->account, 'review_assigned')) {
            Mail::to($email)->send(new ReviewerInvitationMail($review->reviewer));
        }

        return back()->with('ukc_status', 'Relecteur assigné et invité par e-mail.');
    }

    public function removeReview(Submission $submission, Review $review): RedirectResponse
    {
        if ($review->submission_id === $submission->id) {
            $review->delete();
        }

        return back()->with('ukc_status', 'Assignation retirée.');
    }

    /**
     * Attribution automatique des relecteurs (aperçu) : sélectionne une édition,
     * règle la cible et le plafond, propose un plan (matching axe + COI + charge).
     * L'expertise des relecteurs se règle dans l'onglet « Relecteurs » de la fiche
     * d'édition.
     */
    public function autoAssign(Request $request, AutoAssignReviewers $engine): View
    {
        $editions = $this->accessibleEditions($request, 'submissions_list');
        $editionId = (string) $request->query('edition_id', (string) ($editions->first()->id ?? ''));
        $edition = $editionId !== '' ? $editions->firstWhere('id', (int) $editionId) : null;

        $target = max(1, min(5, (int) $request->query('target', 2)));
        $maxLoad = max(0, min(50, (int) $request->query('max_load', 0)));

        $preview = $edition ? $engine->preview($edition, $target, $maxLoad) : null;

        return view('admin.submissions.auto-assign', [
            'editions' => $editions,
            'edition' => $edition,
            'edition_id' => $editionId,
            'target' => $target,
            'maxLoad' => $maxLoad,
            'preview' => $preview,
        ]);
    }

    /** Applique le plan validé (paires soumission→relecteur cochées). */
    public function autoAssignApply(Request $request, AutoAssignReviewers $engine): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['string'],
        ]);

        $edition = Edition::findOrFail($data['edition_id']);

        $pairs = [];
        foreach ($data['pairs'] as $token) {
            [$subId, $revId] = array_pad(explode(':', (string) $token, 2), 2, null);
            if (ctype_digit((string) $subId) && ctype_digit((string) $revId)) {
                $pairs[] = ['submission_id' => (int) $subId, 'reviewer_id' => (int) $revId];
            }
        }

        $created = $engine->apply($edition, $pairs);

        return redirect()
            ->route('admin.submissions.auto-assign', ['edition_id' => $edition->id])
            ->with('ukc_status', $created > 0
                ? $created.' relecteur(s) assigné(s) et invité(s) par e-mail.'
                : 'Aucune nouvelle assignation (déjà en place ou conflit d’intérêt).');
    }

    /**
     * Historique dérivé des vrais événements (dépôt, versions, relectures).
     *
     * @return array<int, array{when: Carbon|null, what: string, done: bool}>
     */
    private function timeline(Submission $s, $reviews): array
    {
        $events = [[
            'when' => $s->created_at,
            'what' => 'Soumission déposée'.($s->correspondingAuthorName() ? ' par '.$s->correspondingAuthorName() : ''),
            'done' => false,
        ]];

        foreach ($s->versions()->get() as $v) {
            $events[] = ['when' => $v->created_at, 'what' => 'Version '.$v->version.' resoumise', 'done' => false];
        }

        foreach ($reviews as $review) {
            $name = $review->reviewer?->full_name ?: 'un relecteur';
            $events[] = ['when' => $review->created_at, 'what' => 'Assignée à '.$name, 'done' => false];
            if ($review->isCompleted()) {
                $reco = $review->recommendationLabel();
                $events[] = ['when' => $review->updated_at, 'what' => 'Avis de '.$name.' reçu'.($reco ? ' — '.$reco : ''), 'done' => true];
            }
        }

        return collect($events)->sortBy(fn (array $e) => $e['when']?->getTimestamp() ?? 0)->values()->all();
    }

    /** L'auteur correspondant (participant lié) a-t-il un paiement réglé sur l'édition ? */
    private function authorHasPaid(Submission $submission): bool
    {
        return $submission->participant?->payments()->where('status', 'paid')->exists() ?? false;
    }
}
