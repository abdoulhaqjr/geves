<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AssignReviewer;
use App\Actions\AutoAssignReviewers;
use App\Actions\LinkSubmissionAuthors;
use App\Actions\UpsertParticipantForAccount;
use App\Events\SubmissionStatusChanged;
use App\Http\Controllers\Controller;
use App\Mail\AuthorLinkedMail;
use App\Mail\ReviewerInvitationMail;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Review;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\BibliographyExporter;
use App\Support\ConflictOfInterest;
use App\Support\ListSort;
use App\Support\Notifier;
use App\Support\Plagiarism\PlagiarismRegistry;
use App\Support\ReviewerExpertise;
use App\Support\ReviewGrid;
use App\Support\SimilarityChecker;
use App\Support\SubmissionFormConfig;
use App\Support\Trend;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
        $type = (string) $request->query('type', '');
        $track = (string) $request->query('track', '');
        $unlinked = $request->boolean('unlinked');

        // Un co-auteur « non rattaché » = lien invited/unmatched/declined (hors déposant).
        $unlinkedScope = fn ($l) => $l->where('role', 'coauthor')->whereIn('status', ['invited', 'unmatched', 'declined']);
        $scoped = $this->scopedEditionIds($request, 'submissions_list');
        $accessible = $this->accessibleEditions($request, 'submissions_list');

        // Édition sélectionnée : si le paramètre est absent (1er accès), on cible le
        // colloque courant par défaut ; s'il est présent mais vide, l'utilisateur a
        // explicitement choisi « Toutes les éditions ».
        $editionId = $request->has('edition_id')
            ? (string) $request->query('edition_id', '')
            : $this->currentEditionId($accessible);

        // « author » est une colonne VIRTUELLE : le nom vient du participant lié,
        // pas de la table submissions - le tri passe donc par une jointure.
        $sort = ListSort::resolve($request, ['ref_code', 'title', 'author', 'type', 'track', 'created_at', 'status'], 'created_at', 'desc');
        $sortByAuthor = $sort['sort'] === 'author';

        $submissions = Submission::query()
            // `reviews` : colonne d'avancement de la relecture (Review::progressOf).
            ->with(['edition', 'participant', 'reviews'])
            ->withCount(['authorLinks as unlinked_authors_count' => $unlinkedScope])
            // ⚠️ Colonnes qualifiées : le tri par auteur joint `participants`, qui
            // porte aussi `edition_id` - sans préfixe, la requête est ambiguë.
            ->where('submissions.revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('submissions.edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('submissions.edition_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('submissions.status', $status))
            ->when($type !== '', fn ($q) => $q->where('submissions.type', $type))
            ->when($track !== '', fn ($q) => $q->where('submissions.track', $track))
            ->when($unlinked, fn ($q) => $q->whereHas('authorLinks', $unlinkedScope))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($sub) => $sub
                    ->where('submissions.title', 'like', $term)
                    ->orWhere('submissions.ref_code', 'like', $term)
                    ->orWhereHas('participant', fn ($p) => $p
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            })
            ->when($sortByAuthor, fn ($q) => $q
                ->leftJoin('participants', 'participants.id', '=', 'submissions.participant_id')
                ->orderBy('participants.last_name', $sort['dir'])
                ->orderBy('participants.first_name', $sort['dir'])
                ->select('submissions.*'))
            ->when(! $sortByAuthor, fn ($q) => $q->orderBy('submissions.'.$sort['sort'], $sort['dir']))
            ->orderBy('submissions.id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Indicateurs par statut (sur l'édition filtrée), indépendants des autres filtres.
        $counts = Submission::query()->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId))
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $c = fn (string $s): int => (int) ($counts[$s] ?? 0);

        // Tendance des dépôts : 7 derniers jours vs les 7 précédents (même périmètre).
        $trendBase = fn () => Submission::query()->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('edition_id', $editionId));
        $trendTotal = Trend::of(
            (int) $trendBase()->whereBetween('created_at', [now()->subDays(7), now()])->count(),
            (int) $trendBase()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(),
        );

        return view('admin.submissions.index', [
            'trendTotal' => $trendTotal,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'submissions' => $submissions,
            'search' => $search,
            'status' => $status,
            'edition_id' => $editionId,
            'type' => $type,
            'track' => $track,
            'unlinked' => $unlinked,
            'editions' => $accessible,
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

    /**
     * Identifiant du colloque courant par défaut (édition ouverte, sinon la plus
     * récente accessible). Vide si aucune édition accessible.
     *
     * @param  Collection<int, Edition>  $editions
     */
    private function currentEditionId($editions): string
    {
        if ($editions->isEmpty()) {
            return '';
        }

        $current = $editions->firstWhere('status', 'open') ?? $editions->first();

        return (string) $current->id;
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
            // ⚠️ Colonnes qualifiées : le tri par auteur joint `participants`, qui
            // porte aussi `edition_id` - sans préfixe, la requête est ambiguë.
            ->where('submissions.revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('submissions.edition_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('submissions.edition_id', $editionId))
            ->when($status !== '', fn ($q) => $q->where('submissions.status', $status))
            ->when($type !== '', fn ($q) => $q->where('submissions.type', $type))
            ->when($track !== '', fn ($q) => $q->where('submissions.track', $track))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($sub) => $sub
                    ->where('submissions.title', 'like', $term)
                    ->orWhere('submissions.ref_code', 'like', $term)
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

    /** Export bibliographique (actes) des communications ACCEPTÉES d'une édition. */
    public function bibliography(Request $request, string $format, BibliographyExporter $exporter): StreamedResponse
    {
        abort_unless(in_array($format, ['bibtex', 'ris'], true), 404);

        $editions = $this->accessibleEditions($request, 'submissions_list');
        $editionId = (string) $request->query('edition_id', '');
        $edition = $editionId !== '' ? $editions->firstWhere('id', (int) $editionId) : null;
        $edition ??= $editions->first();
        abort_if($edition === null, 404);

        $submissions = Submission::where('edition_id', $edition->id)
            ->where('revision_of', 0)->where('status', 'accepted')
            ->with('participant')->orderBy('ref_code')->get();

        $content = $format === 'bibtex' ? $exporter->bibtex($submissions, $edition) : $exporter->ris($submissions, $edition);
        $ext = $format === 'bibtex' ? 'bib' : 'ris';
        $mime = $format === 'bibtex' ? 'application/x-bibtex' : 'application/x-research-info-systems';
        $name = 'actes-'.($edition->slug ?: $edition->id).'-'.($edition->year ?: date('Y')).'.'.$ext;

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $name, ['Content-Type' => $mime.'; charset=UTF-8']);
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
            'authorLinks' => $submission->authorLinks()->with('user')->orderBy('author_index')->get(),
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
            'reviewCriteria' => ReviewGrid::criteria($submission->edition),
            'reviewScale' => ReviewGrid::SCALE,
        ]);
    }

    /** Rattache manuellement un auteur à un compte (par e-mail) - correction d'un rapprochement. */
    public function linkAuthor(Request $request, Submission $submission, SubmissionAuthor $link, UpsertParticipantForAccount $upsert): RedirectResponse
    {
        abort_unless($link->submission_id === $submission->id, 404);

        $data = $request->validate(
            ['email' => ['required', 'email']],
            ['email.required' => 'Indiquez l’e-mail du compte à rattacher.']
        );

        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])->first();
        if (! $user) {
            return back()->with('ukc_error', 'Aucun compte ne correspond à cet e-mail.');
        }

        $edition = $submission->edition;
        $upsert->handle($edition, $user, $link->role === 'corresponding' ? 'author' : 'coauthor');

        $link->update(['user_id' => $user->id, 'status' => 'linked', 'match_type' => 'manual', 'notified_at' => now()]);

        Notifier::account(
            $user,
            'authorship',
            __('Vous êtes associé(e) à une communication'),
            '« '.$submission->title.' » - '.($edition->title ?? ''),
            route('account.submissions.show', $submission),
            'inbox',
        );
        if (Notifier::emailAllowed($user, 'authorship')) {
            Mail::to($user->email)->send(new AuthorLinkedMail(
                $submission,
                $user->full_name ?: (string) $user->name,
                LinkSubmissionAuthors::roleLabel($link->role),
                route('account.submissions.show', $submission),
                route('author.decline', ['token' => $link->token]),
            ));
        }

        return back()->with('ukc_status', 'Auteur rattaché au compte '.($user->full_name ?: $user->email).'.');
    }

    /** Détache un auteur d'un compte (rapprochement erroné). Le déposant est intouchable. */
    public function detachAuthor(Submission $submission, SubmissionAuthor $link): RedirectResponse
    {
        abort_unless($link->submission_id === $submission->id, 404);

        if ($link->match_type === 'self') {
            return back()->with('ukc_error', 'Le déposant ne peut pas être détaché.');
        }

        $link->update(['user_id' => null, 'status' => 'unmatched', 'match_type' => null]);

        return back()->with('ukc_status', 'Compte détaché de cet auteur.');
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
            // Message d'API (à plat « message » ou imbriqué « error.message » selon le fournisseur).
            $apiMsg = trim((string) ($e->response->json('message') ?? $e->response->json('error.message') ?? ''));

            return back()->with('ukc_error', match (true) {
                $status === 429 => 'Quota du fournisseur anti-plagiat atteint. Réessayez plus tard.',
                in_array($status, [401, 403], true) => 'Accès '.$provider->label().' refusé'.($apiMsg !== '' ? ' - '.$apiMsg : '').'. Vérifiez la configuration dans Réglages · Intégrations.',
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

        return back()->with('ukc_status', 'Statut mis à jour : '.$submission->statusLabel().' - l’auteur a été notifié.');
    }

    /**
     * Ouvre (ou referme) la phase de réponse aux relecteurs (rebuttal) : révèle à
     * l'auteur les commentaires « à l'auteur » et l'autorise à y répondre avant
     * la décision finale.
     */
    public function toggleRebuttal(Submission $submission): RedirectResponse
    {
        $meta = $submission->meta ?? [];
        $rebuttal = is_array($meta['rebuttal'] ?? null) ? $meta['rebuttal'] : [];

        if ($submission->rebuttalOpen()) {
            $rebuttal['opened_at'] = null;
            $meta['rebuttal'] = $rebuttal;
            $submission->update(['meta' => $meta]);

            return back()->with('ukc_status', 'Phase de réponse fermée.');
        }

        $rebuttal['opened_at'] = now()->toIso8601String();
        $meta['rebuttal'] = $rebuttal;
        $submission->update(['meta' => $meta]);

        Notifier::account(
            $submission->participant?->user,
            kind: 'rebuttal',
            title: 'Phase de réponse ouverte',
            body: 'Vous pouvez consulter les retours des relecteurs et y répondre pour « '.$submission->title.' ».',
            url: route('account.submissions.show', $submission),
            icon: 'pen',
        );

        return back()->with('ukc_status', 'Phase de réponse ouverte - l’auteur a été notifié.');
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
        if (Notifier::emailAllowed($review->reviewer->user, 'review_assigned')) {
            Mail::to($email)->send(new ReviewerInvitationMail($review->reviewer, review: $review));
        }

        return back()->with('ukc_status', 'Relecteur assigné et invité par e-mail.');
    }

    /**
     * Retire une assignation. ⚠️ On n'efface pas la ligne : une relecture est une
     * pièce du dossier (qui a été sollicité, quand, et ce qu'il a éventuellement
     * rendu). Elle passe « annulée » - hors des compteurs et de la couverture
     * (cf. Review::ACTIVE_STATUSES) mais toujours lisible dans l'historique.
     */
    public function removeReview(Submission $submission, Review $review): RedirectResponse
    {
        if ($review->submission_id !== $submission->id || $review->status === 'cancelled') {
            return back()->with('ukc_status', 'Assignation retirée.');
        }

        $wasCompleted = $review->isCompleted();
        $review->update(['status' => 'cancelled']);

        // Le relecteur doit savoir qu'il n'a plus cette relecture à rendre.
        $review->loadMissing('reviewer.user');
        Notifier::account(
            $review->reviewer?->user,
            kind: 'review_assigned',
            title: 'Relecture annulée',
            body: 'Une relecture qui vous avait été confiée a été retirée par le comité.',
            url: route('account.reviews'),
            icon: 'inbox',
        );

        return back()->with('ukc_status', $wasCompleted
            ? 'Assignation annulée : l’évaluation reste consultable dans l’historique mais ne compte plus.'
            : 'Assignation annulée : la place est libérée pour un autre relecteur.');
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
        $editionId = $request->has('edition_id')
            ? (string) $request->query('edition_id', '')
            : $this->currentEditionId($editions);
        $edition = $editionId !== '' ? $editions->firstWhere('id', (int) $editionId) : null;

        // ⚠️ Les paramètres du moteur sont des RÈGLES de l'édition (onglet
        // Évaluation), plus des paramètres d'URL : le plan proposé ne dépend donc
        // pas de la façon dont on est arrivé sur la page.
        $target = ReviewGrid::target($edition);
        $maxLoad = ReviewGrid::maxLoad($edition);

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

    /**
     * Onglet « Relecteurs & expertise » : la matrice des axes que chaque
     * relecteur peut évaluer, et sa charge. Elle vivait dans la fiche d'édition,
     * loin du moteur qui la consomme et de la page où l'on constate qu'un axe
     * n'est couvert par personne.
     */
    public function reviewers(Request $request): View
    {
        $editions = $this->accessibleEditions($request, 'submissions_list');
        $editionId = $request->has('edition_id')
            ? (string) $request->query('edition_id', '')
            : $this->currentEditionId($editions);
        $edition = $editionId !== '' ? $editions->firstWhere('id', (int) $editionId) : null;

        $reviewers = ReviewerExpertise::forEdition($edition);
        $axes = ReviewerExpertise::axes($edition);

        // Couverture : un axe que personne ne peut évaluer est le vrai signal de
        // cette page - sans lui, l'attribution retombera sur « hors axe ».
        $uncovered = array_values(array_filter(
            $axes,
            fn (string $axis) => ! $reviewers->contains(
                fn (array $r) => ReviewerExpertise::covers($r['tracks'], $axis)
            )
        ));

        return view('admin.submissions.reviewers', [
            'editions' => $editions,
            'edition' => $edition,
            'edition_id' => $editionId,
            'reviewers' => $reviewers,
            'reviewerAxes' => $axes,
            'uncoveredAxes' => $uncovered,
            'avgLoad' => $reviewers->isEmpty() ? 0.0 : round($reviewers->avg('load'), 1),
            'canWrite' => $this->canWriteEdition($edition),
        ]);
    }

    /**
     * Onglet « Options » : toutes les RÈGLES du module Soumissions pour cette
     * édition - dépôt (limites du formulaire), attribution (relecteurs visés,
     * plafond de charge) et évaluation (grille, délai). Elles étaient éclatées
     * entre la page d'attribution (en paramètres d'URL), la fiche d'édition et
     * l'éditeur de formulaire.
     */
    public function options(Request $request): View
    {
        $editions = $this->accessibleEditions($request, 'submissions_list');
        $editionId = $request->has('edition_id')
            ? (string) $request->query('edition_id', '')
            : $this->currentEditionId($editions);
        $edition = $editionId !== '' ? $editions->firstWhere('id', (int) $editionId) : null;

        return view('admin.submissions.options', [
            'editions' => $editions,
            'edition' => $edition,
            'edition_id' => $editionId,
            'reviewCriteria' => ReviewGrid::criteria($edition),
            'reviewScale' => ReviewGrid::SCALE,
            'reviewDueDays' => ReviewGrid::dueDays($edition),
            'target' => ReviewGrid::target($edition),
            'maxLoad' => ReviewGrid::maxLoad($edition),
            'formRules' => (new SubmissionFormConfig($edition ?? new Edition))->rules(),
            'canWrite' => $this->canWriteEdition($edition),
        ]);
    }

    /**
     * Ces deux onglets vivent dans le module Soumissions (capacité
     * `submissions_list`) mais écrivent dans les réglages de l'ÉDITION : sans ce
     * contrôle, on afficherait des formulaires qui finiraient en 403.
     */
    private function canWriteEdition(?Edition $edition): bool
    {
        return $edition !== null && in_array(
            $edition->id,
            Auth::guard('web')->user()?->accessibleEditionIds('editions_write') ?? [],
            true,
        );
    }

    /** Applique le plan validé (paires soumission→relecteur cochées). */
    public function autoAssignApply(Request $request, AutoAssignReviewers $engine): RedirectResponse
    {
        $data = $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['string'],
            // Justification proposée par le moteur, archivée sur la relecture.
            'reasons' => ['array'],
            'reasons.*' => ['nullable', 'string', 'max:200'],
        ]);

        $edition = Edition::findOrFail($data['edition_id']);

        $pairs = [];
        foreach ($data['pairs'] as $token) {
            [$subId, $revId] = array_pad(explode(':', (string) $token, 2), 2, null);
            if (ctype_digit((string) $subId) && ctype_digit((string) $revId)) {
                $reason = $data['reasons'][$token] ?? null;
                $pairs[] = [
                    'submission_id' => (int) $subId,
                    'reviewer_id' => (int) $revId,
                    'reason' => $reason !== null ? (string) $reason : null,
                ];
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
                $events[] = ['when' => $review->updated_at, 'what' => 'Relecture de '.$name.' rendue'.($reco ? ' - '.$reco : ''), 'done' => true];
            }
        }

        return collect($events)->sortBy(fn (array $e) => $e['when']?->getTimestamp() ?? 0)->values()->all();
    }

    /** L'auteur correspondant (participant lié) a-t-il un paiement réglé sur l'édition ? */
    private function authorHasPaid(Submission $submission): bool
    {
        return $submission->participant?->participationPayments()->where('status', 'paid')->exists() ?? false;
    }
}
