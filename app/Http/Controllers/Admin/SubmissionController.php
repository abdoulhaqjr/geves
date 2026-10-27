<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AssignReviewer;
use App\Actions\AutoAssignReviewers;
use App\Actions\DeleteSubmission;
use App\Actions\LinkSubmissionAuthors;
use App\Actions\RegisterEventReviewer;
use App\Actions\UpdateSubmissionAuthors;
use App\Actions\UpsertParticipantForAccount;
use App\Events\SubmissionStatusChanged;
use App\Http\Controllers\Admin\Concerns\ScopesEditionAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSubmissionAuthorsRequest;
use App\Mail\AuthorLinkedMail;
use App\Mail\ReviewerInvitationMail;
use App\Mail\ReviewerRegistrationMail;
use App\Models\Attestation;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Review;
use App\Models\ReviewerApplication;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use App\Support\BibliographyExporter;
use App\Support\ConflictOfInterest;
use App\Support\EventNameFormat;
use App\Support\ListSort;
use App\Support\Notifier;
use App\Support\ParticipantTypes;
use App\Support\Plagiarism\PlagiarismRegistry;
use App\Support\ReviewerExpertise;
use App\Support\ReviewerVolunteering;
use App\Support\ReviewGrid;
use App\Support\SimilarityChecker;
use App\Support\SubmissionFormConfig;
use App\Support\Trend;
use App\Support\UserDirectorySearch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Soumissions du back-office (contrôleur + Blade) : liste filtrable + fiche
 * détaillée avec workflow (mise en évaluation, correction, acceptation, refus)
 * notifiant l'auteur, et gestion des évaluateurs (assignation, retrait, synthèse,
 * conflits d'intérêts).
 */
class SubmissionController extends Controller
{
    use ScopesEditionAccess;

    /** Statuts que l'on peut appliquer depuis la fiche (le dépôt initial exclu). */
    public const ACTIONS = [
        'under_review' => ['Mettre en évaluation', 'ghost'],
        'revision' => ['Demander une correction', 'gold'],
        'accepted' => ['Accepter', 'green'],
        'rejected' => ['Refuser', 'danger'],
    ];

    /**
     * Un co-auteur « non rattaché » = lien invited/unmatched/declined (hors
     * déposant). Une seule définition : la liste la compte et le filtre la
     * cherche, les deux doivent désigner les mêmes lignes.
     */
    private static function liensNonRattaches(): \Closure
    {
        return fn ($l) => $l->where('role', 'coauthor')->whereIn('status', ['invited', 'unmatched', 'declined']);
    }

    /**
     * Filtres de la liste, appliqués à un constructeur de requête déjà cadré
     * sur l'événement.
     *
     * ⚠️⚠️ **UNE SEULE CONSTRUCTION, POUR L'ÉCRAN ET POUR SES EXPORTS.** La
     * liste et le CSV dressaient la même chaîne chacun de leur côté : ajouter
     * un filtre d'un côté seulement faisait exporter autre chose que ce qu'on
     * regarde, sans rien pour s'en apercevoir. Même leçon qu'`AttestationQuery`.
     *
     * @param  Builder<Submission>  $query
     * @return Builder<Submission>
     */
    private function filtre(Request $request, Builder $query): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $presentationMode = (string) $request->query('presentation_mode', '');
        $track = (string) $request->query('track', '');
        $reviews = $this->etatDesEvaluations($request);

        return $query
            // ⚠️ Colonnes qualifiées : le tri par auteur joint `participants`,
            // qui porte aussi `event_id` - sans préfixe, la requête est ambiguë.
            ->when($status !== '', fn ($q) => $status === Submission::STATUS_IN_REVIEW
                ? $q->whereIn('submissions.status', Submission::IN_REVIEW_STATUSES)
                : $q->where('submissions.status', $status))
            ->when($reviews !== '', fn ($q) => $q->reviewsState($reviews))
            ->when($type !== '', fn ($q) => $q->where('submissions.type', $type))
            ->when(in_array($presentationMode, ['online', 'onsite'], true), fn ($q) => $q->where('submissions.presentation_mode', $presentationMode))
            ->when($track !== '', fn ($q) => $q->where('submissions.track', $track))
            ->when($request->boolean('resubmitted'), fn ($q) => $q->resubmitted())
            ->when($request->boolean('unlinked'), fn ($q) => $q->whereHas('authorLinks', self::liensNonRattaches()))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($sub) => $sub
                    ->where('submissions.title', 'like', $term)
                    ->orWhere('submissions.ref_code', 'like', $term)
                    // Les co-auteurs, même sans compte lié, sont conservés dans ce JSON.
                    ->orWhere('submissions.authors', 'like', $term)
                    ->orWhereHas('participant', fn ($p) => $p
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            });
    }

    /**
     * État d'évaluation demandé, filtré sur les clés connues.
     *
     * ⚠️ Une valeur fantaisiste est IGNORÉE, pas appliquée : appliquée, elle
     * rendrait une liste vide sans que la barre de filtres n'explique pourquoi.
     */
    private function etatDesEvaluations(Request $request): string
    {
        $state = (string) $request->query('reviews', '');

        return array_key_exists($state, Submission::REVIEW_STATES) ? $state : '';
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $presentationMode = (string) $request->query('presentation_mode', '');
        $track = (string) $request->query('track', '');
        $unlinked = $request->boolean('unlinked');
        $resubmitted = $request->boolean('resubmitted');
        $reviews = $this->etatDesEvaluations($request);

        // Un co-auteur « non rattaché » = lien invited/unmatched/declined (hors déposant).
        $unlinkedScope = self::liensNonRattaches();
        $scoped = $this->scopedEditionIds($request, 'submissions_list');
        $accessible = $this->accessibleEditions($request, 'submissions_list');

        // Événement sélectionnée : si le paramètre est absent (1er accès), on cible le
        // colloque courant par défaut ; s'il est présent mais vide, l'utilisateur a
        // explicitement choisi « Toutes les événements ».
        $editionId = $request->has('event_id')
            ? (string) $request->query('event_id', '')
            : $this->currentEditionId($accessible);

        // « author » est une colonne VIRTUELLE : le nom vient du participant lié,
        // pas de la table submissions - le tri passe donc par une jointure.
        $sort = ListSort::resolve($request, ['ref_code', 'title', 'author', 'type', 'track', 'created_at', 'status'], 'created_at', 'desc');
        $sortByAuthor = $sort['sort'] === 'author';

        $base = Submission::query()
            // `reviews` : colonne d'avancement de l’évaluation (Review::progressOf).
            ->with(['event', 'participant', 'reviews'])
            ->withCount(['authorLinks as unlinked_authors_count' => $unlinkedScope])
            // ⚠️ Colonnes qualifiées : le tri par auteur joint `participants`, qui
            // porte aussi `event_id` - sans préfixe, la requête est ambiguë.
            ->where('submissions.revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('submissions.event_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('submissions.event_id', $editionId));

        $submissions = $this->filtre($request, $base)
            ->when($sortByAuthor, fn ($q) => $q
                ->leftJoin('participants', 'participants.id', '=', 'submissions.participant_id')
                ->orderBy('participants.last_name', $sort['dir'])
                ->orderBy('participants.first_name', $sort['dir'])
                ->select('submissions.*'))
            ->when(! $sortByAuthor, fn ($q) => $q->orderBy('submissions.'.$sort['sort'], $sort['dir']))
            ->orderBy('submissions.id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Indicateurs par statut (sur l'événement filtrée), indépendants des autres filtres.
        $counts = Submission::query()->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('event_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId))
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $c = fn (string $s): int => (int) ($counts[$s] ?? 0);

        // ⚠️⚠️ **LA FILE DE TRAVAIL DU COMITÉ** : encore dans le circuit ET
        // toutes les évaluations demandées rendues. C'est le seul indicateur
        // qui appelle un geste - les quatre autres décrivent un état.
        $toDecide = (int) Submission::query()->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('event_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId))
            ->whereIn('status', Submission::IN_REVIEW_STATUSES)
            ->reviewsState('done')
            ->count();

        // Le périmètre de l'événement, sans aucun filtre de la barre : les
        // indicateurs, la tendance et les pastilles de subdivision en repartent.
        $perimetre = fn () => Submission::query()->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('event_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId));
        $trendTotal = Trend::of(
            (int) $perimetre()->whereBetween('created_at', [now()->subDays(7), now()])->count(),
            (int) $perimetre()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(),
        );

        // ⚠️⚠️ **CE QUE LES LIENS DES CARTES DOIVENT EMPORTER.** Tout ce que la
        // barre porte SAUF les deux dimensions que les cartes gouvernent
        // (statut et avancement des évaluations) : sans cela, cliquer une carte
        // jetterait l'axe, le type ou la recherche en cours. Les valeurs vides
        // sont retirées - une URL ne porte que des filtres qui filtrent.
        $contexte = array_filter([
            'search' => $search,
            'type' => $type,
            'presentation_mode' => $presentationMode,
            'track' => $track,
            'unlinked' => $unlinked ? 1 : null,
            'resubmitted' => $resubmitted ? 1 : null,
        ], fn ($v) => $v !== null && $v !== '');

        // ⚠️⚠️ **L'EXPORT PART DES MÊMES FILTRES, PAR LA MÊME CONSTRUCTION.** Le
        // lien recopiait à la main la liste des paramètres à emporter, et il en
        // avait oublié DEUX - l'avancement des évaluations et les co-auteurs non
        // rattachés : on exportait depuis « Toutes rendues » et le CSV sortait
        // toute la sélection de statut. `filtre()` les lit tous les deux ; seul
        // le lien ne les écrivait pas. Une seule source, elle ne peut plus
        // diverger. ⚠️ Un événement vide = « tous », et l'export l'entend ainsi
        // (`event_id` absent) : le retirer ne change donc pas le périmètre.
        $exportUrl = route('admin.submissions.export', array_filter([
            'event_id' => $editionId,
            'status' => $status,
            'reviews' => $reviews,
        ], fn ($v) => $v !== '') + $contexte);

        $chiffres = [
            'total' => (int) $counts->sum(),
            'toDecide' => $toDecide,
            'underReview' => $c('under_review') + $c('submitted'),
            'accepted' => $c('accepted'),
            'revision' => $c('revision'),
            'rejected' => $c('rejected'),
        ];

        return view('admin.submissions.index', [
            'trendTotal' => $trendTotal,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'submissions' => $submissions,
            'search' => $search,
            'status' => $status,
            'reviews' => $reviews,
            'event_id' => $editionId,
            'type' => $type,
            'presentationMode' => $presentationMode,
            'track' => $track,
            'unlinked' => $unlinked,
            'resubmitted' => $resubmitted,
            'events' => $accessible,
            'statuses' => Submission::statuses(),
            'reviewStates' => Submission::REVIEW_STATES,
            'statusPills' => Submission::statusPills(),
            'tracks' => Submission::query()->where('revision_of', 0)
                ->when($scoped !== null, fn ($q) => $q->whereIn('event_id', $scoped))
                ->when($editionId !== '', fn ($q) => $q->where('event_id', $editionId))
                ->whereNotNull('track')->where('track', '!=', '')->distinct()->orderBy('track')->pluck('track'),
            'kpis' => $this->indicateurs($editionId, $status, $reviews, $contexte, $chiffres),
            'exportUrl' => $exportUrl,
            'resetUrl' => route('admin.submissions.index', ['event_id' => $editionId]),
            'filtreActif' => $status !== '' || $reviews !== '' || $contexte !== [],
        ]);
    }

    /**
     * Les indicateurs du haut, chacun porteur du filtre qu'il décrit.
     *
     * ⚠️⚠️ **UNE CARTE CLIQUABLE DOIT OUVRIR EXACTEMENT CE QU'ELLE COMPTE.**
     * « En revue » additionne deux statuts (déposée + en évaluation) : visée
     * par un `status=under_review`, elle aurait rendu moins de lignes que son
     * propre chiffre, et un total qui baisse sans explication se lit comme une
     * panne. D'où le filtre composite {@see Submission::STATUS_IN_REVIEW}, qui
     * s'affiche aussi dans la barre de filtres - l'écran ne doit jamais filtrer
     * en silence.
     *
     * ⚠️ Les chiffres restent calculés sur le **périmètre de l'événement**, pas
     * sur les filtres courants : ce sont des repères de navigation ; s'ils
     * suivaient le filtre, la carte sur laquelle on vient de cliquer afficherait
     * le total de sa propre sélection et les autres tomberaient à zéro.
     *
     * @param  array<string, scalar>  $contexte  les autres filtres de la barre, que le lien EMPORTE
     * @param  array<string, int>  $n
     * @return list<array<string, mixed>>
     */
    private function indicateurs(string $editionId, string $status, string $reviews, array $contexte, array $n): array
    {
        $cartes = [
            ['cle' => 'total', 'label' => 'Total', 'icone' => 'inbox', 'teinte' => 'blue', 'val' => 'blue', 'filtre' => []],
            ['cle' => 'toDecide', 'label' => 'À décider', 'icone' => 'flag', 'teinte' => 'amber', 'val' => 'warn',
                'filtre' => ['status' => Submission::STATUS_IN_REVIEW, 'reviews' => 'done']],
            ['cle' => 'underReview', 'label' => 'En revue', 'icone' => 'clock', 'teinte' => 'slate', 'val' => '',
                'filtre' => ['status' => Submission::STATUS_IN_REVIEW],
                // Les anciennes URL de statut précis restent rattachées à
                // cette carte, même si les onglets de subdivision ont disparu.
                'allume' => [Submission::STATUS_IN_REVIEW, ...Submission::IN_REVIEW_STATUSES]],
            ['cle' => 'accepted', 'label' => 'Acceptées', 'icone' => 'check-circle', 'teinte' => 'green', 'val' => 'green',
                'filtre' => ['status' => 'accepted']],
            ['cle' => 'revision', 'label' => 'Corrections', 'icone' => 'pen', 'teinte' => 'violet', 'val' => 'warn',
                'filtre' => ['status' => 'revision']],
            ['cle' => 'rejected', 'label' => 'Refusées', 'icone' => 'close', 'teinte' => 'red', 'val' => 'err',
                'filtre' => ['status' => 'rejected']],
        ];

        return array_map(function (array $carte) use ($editionId, $status, $reviews, $contexte, $n): array {
            $filtre = $carte['filtre'];
            // Allumée seulement si les DEUX dimensions correspondent : sinon
            // « En revue » resterait allumée sous « À décider », qui la restreint.
            $active = in_array($status, (array) ($carte['allume'] ?? [$filtre['status'] ?? '']), true)
                && ($filtre['reviews'] ?? '') === $reviews;

            return $carte + [
                'valeur' => $n[$carte['cle']] ?? 0,
                // ⚠️⚠️ **LE LIEN EMPORTE LES AUTRES FILTRES.** Il ne portait que
                // l'événement et son statut : on filtrait un axe, on cliquait une
                // carte, et l'axe disparaissait sans un mot. Une carte change le
                // statut, elle ne défait pas le travail de qui l'a cliquée.
                // ⚠️ Et la carte ALLUMÉE s'éteint : c'est la sortie du filtre
                // sans passer par « Total », qui emporterait tout le reste.
                'url' => route('admin.submissions.index', ['event_id' => $editionId] + ($active ? [] : $filtre) + $contexte),
                'active' => $active,
            ];
        }, $cartes);
    }

    /** Empêche qu'un texte fourni par un auteur soit exécuté comme formule dans un tableur. */
    private static function csvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^(?:\s*[=+\-@]|[\t\r])/u', $text) ? "'".$text : $text;
    }

    /** Export CSV des soumissions filtrées (mêmes filtres que la liste). */
    public function export(Request $request): StreamedResponse
    {
        $editionId = (string) $request->query('event_id', '');
        $scoped = $this->scopedEditionIds($request, 'submissions_list');

        $base = Submission::query()
            ->with(['event', 'participant'])
            // ⚠️ Colonnes qualifiées : le tri par auteur joint `participants`, qui
            // porte aussi `event_id` - sans préfixe, la requête est ambiguë.
            ->where('submissions.revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('submissions.event_id', $scoped))
            ->when($editionId !== '', fn ($q) => $q->where('submissions.event_id', $editionId));

        $rows = $this->filtre($request, $base)->latest('submissions.id')->get();

        $statuses = Submission::statuses();

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Référence', 'Titre', 'Déposant', 'Type de communication', 'Mode de présentation', 'Participation au colloque', 'Axe', 'Statut', 'Déposée le', 'Événement']);
            foreach ($rows as $s) {
                fputcsv($out, array_map(self::csvCell(...), [
                    $s->ref_code,
                    $s->title,
                    $s->participant?->full_name ?? $s->correspondingAuthorName(),
                    $s->type === 'poster' ? 'Poster' : 'Orale',
                    $s->presentationModeLabel() ?? '',
                    $s->participant?->participationModeLabel() ?? '',
                    (string) $s->track,
                    $statuses[$s->status] ?? $s->status,
                    $s->created_at?->format('d/m/Y H:i'),
                    $s->event?->title ?: $s->event?->year,
                ]));
            }
            fclose($out);
        }, 'soumissions-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Export bibliographique (actes) des communications ACCEPTÉES d'un événement. */
    public function bibliography(Request $request, string $format, BibliographyExporter $exporter): StreamedResponse
    {
        abort_unless(in_array($format, ['bibtex', 'ris'], true), 404);

        $events = $this->accessibleEditions($request, 'submissions_list');
        $editionId = (string) $request->query('event_id', '');
        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;
        $event ??= $events->first();
        abort_if($event === null, 404);

        $submissions = Submission::where('event_id', $event->id)
            ->where('revision_of', 0)->where('status', 'accepted')
            ->with('participant')->orderBy('ref_code')->get();

        $content = $format === 'bibtex' ? $exporter->bibtex($submissions, $event) : $exporter->ris($submissions, $event);
        $ext = $format === 'bibtex' ? 'bib' : 'ris';
        $mime = $format === 'bibtex' ? 'application/x-bibtex' : 'application/x-research-info-systems';
        $name = 'actes-'.($event->slug ?: $event->id).'-'.($event->year ?: date('Y')).'.'.$ext;

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $name, ['Content-Type' => $mime.'; charset=UTF-8']);
    }

    public function show(Request $request, Submission $submission, SimilarityChecker $similarity, PlagiarismRegistry $plagiarism): View
    {
        $submission->load(['event', 'participant']);

        // Les affectations annulées ne figurent plus sur la fiche. Une personne
        // retirée du vivier n'y reste que si son évaluation a été rendue.
        $reviews = $submission->reviews()->with('reviewer')->get()
            ->filter(fn (Review $review) => self::visibleReview($review))
            ->values();
        $completed = $reviews->where('status', 'completed');

        // Similarité interne (gratuite) : autres soumissions les plus proches,
        // restreintes aux événements accessibles au membre du staff.
        $scoped = $this->scopedEditionIds($request, 'submissions_list');
        $corpus = Submission::query()
            ->whereKeyNot($submission->id)
            ->where('revision_of', 0)
            ->when($scoped !== null, fn ($q) => $q->whereIn('event_id', $scoped))
            ->get(['id', 'ref_code', 'title', 'abstract', 'event_id']);
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

        $existingReviewers = Participant::where('event_id', $submission->event_id)
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
            // ⚠️ Masquer le bouton ne ferme aucune porte - la route porte la
            // même capacité. C'est pour ne pas montrer un geste que l'évaluateur
            // ou le secrétariat ne peuvent pas faire.
            'peutEcrireAuteurs' => (bool) $request->user()?->canOnEdition('editions_write', $submission->event_id),
            // ⚠️ Préparé ICI, pas dans la vue : cet écran emploie la forme EN
            // LIGNE de la directive PHP de Blade, et y ouvrir un bloc ferait
            // avaler tout ce qui suit par le compilateur.
            // `origine` est la place ACTUELLE de l'auteur : c'est elle qui
            // rattache la ligne du formulaire à son lien de compte.
            // ⚠️ Beaucoup d'auteurs n'ont que `name` (imports, anciens dépôts) :
            // sans cette séparation, le formulaire s'ouvrirait avec des champs
            // « Nom » vides et obligatoires, et il faudrait retaper chaque nom
            // pour ajouter un seul co-auteur.
            'auteursPourEdition' => collect($submission->authors ?? [])->map(function ($a, $i) {
                [$prenom, $nom] = EventNameFormat::decouper($a);

                return [
                    'origine' => $i,
                    'first_name' => $prenom,
                    'last_name' => $nom,
                    'email' => (string) ($a['email'] ?? ''),
                    'phone' => (string) ($a['phone'] ?? ''),
                    'affiliation' => (string) ($a['affiliation'] ?? ''),
                    'grade' => (string) ($a['grade'] ?? ''),
                ];
            })->values()->all(),
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
            'reviewCriteria' => ReviewGrid::criteria($submission->event),
            'reviewScale' => ReviewGrid::SCALE,
        ]);
    }

    /** Suppression explicite d'une communication depuis la liste. */
    public function destroy(Request $request, Submission $submission, DeleteSubmission $delete): RedirectResponse
    {
        abort_unless((int) $submission->revision_of === 0, 404);

        $reference = $submission->ref_code;
        $delete->handle($submission, $request->user());

        return back()->with('ukc_status', 'Résumé '.$reference.' supprimé.');
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

        $event = $submission->event;
        $upsert->handle($event, $user, $link->role === 'corresponding' ? 'author' : 'coauthor');

        $link->update(['user_id' => $user->id, 'status' => 'linked', 'match_type' => 'manual', 'notified_at' => now()]);

        Notifier::account(
            $user,
            'authorship',
            __('Vous êtes associé(e) à une communication'),
            '« '.$submission->title.' » - '.($event->title ?? ''),
            route('account.submissions.show', $submission),
            'inbox',
        );
        if (Notifier::emailAllowed($user, 'authorship')) {
            Mail::to($user->email)->send(new AuthorLinkedMail(
                $submission,
                EventNameFormat::user($event, $user),
                LinkSubmissionAuthors::roleLabel($link->role),
                route('account.submissions.show', $submission),
                route('author.decline', ['token' => $link->token]),
            ));
        }

        return back()->with('ukc_status', 'Auteur rattaché au compte '.EventNameFormat::user($event, $user).'.');
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

    /**
     * Corrige la liste des auteurs (omission, faute de frappe, ordre).
     *
     * Les auteurs ne sont pas rééditables par le participant : sa page de
     * correction ne reprend que titre, résumé, mots-clés et fichier. C'est donc
     * au gestionnaire qu'on demande d'ajouter le co-auteur oublié.
     */
    public function updateAuthors(
        UpdateSubmissionAuthorsRequest $request,
        Submission $submission,
        UpdateSubmissionAuthors $action,
    ): RedirectResponse {
        $bilan = $action->handle($submission, (array) $request->validated('auteurs'), $request->user());

        $dit = [];
        if ($bilan['ajoutes'] > 0) {
            // On annonce la notification, parce qu'elle part VRAIMENT : un
            // co-auteur ajouté reçoit le même message qu'au dépôt.
            $dit[] = $bilan['ajoutes'].' '.($bilan['ajoutes'] > 1 ? 'co-auteurs ajoutés et prévenus' : 'co-auteur ajouté et prévenu');
        }
        if ($bilan['retires'] > 0) {
            $dit[] = $bilan['retires'].' '.($bilan['retires'] > 1 ? 'auteurs retirés' : 'auteur retiré');
        }

        return back()->with('ukc_status', 'Liste des auteurs enregistrée'.($dit === [] ? '.' : ' - '.implode(', ', $dit).'.'));
    }

    /** Lance une vérification anti-plagiat web (à la demande) et met le résultat en cache. */
    public function checkPlagiarism(Submission $submission, PlagiarismRegistry $plagiarism): RedirectResponse
    {
        $provider = $plagiarism->active();
        if ($provider === null) {
            return back()->with('ukc_error', 'Aucun fournisseur anti-plagiat web n’est configuré (Réglages - Intégrations).');
        }

        try {
            $result = $provider->check((string) $submission->abstract);
        } catch (RequestException $e) {
            $status = $e->response->status();
            // Message d'API (à plat « message » ou imbriqué « error.message » selon le fournisseur).
            $apiMsg = trim((string) ($e->response->json('message') ?? $e->response->json('error.message') ?? ''));

            return back()->with('ukc_error', match (true) {
                $status === 429 => 'Quota du fournisseur anti-plagiat atteint. Réessayez plus tard.',
                in_array($status, [401, 403], true) => 'Accès '.$provider->label().' refusé'.($apiMsg !== '' ? ' - '.$apiMsg : '').'. Vérifiez la configuration dans Réglages - Intégrations.',
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
     * Ouvre (ou referme) la phase de réponse aux évaluateurs (rebuttal) : révèle à
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
            body: 'Vous pouvez consulter les retours des évaluateurs et y répondre pour « '.$submission->title.' ».',
            url: route('account.submissions.show', $submission),
            icon: 'pen',
        );

        return back()->with('ukc_status', 'Phase de réponse ouverte - l’auteur a été notifié.');
    }

    public function assignReviewer(Request $request, Submission $submission, AssignReviewer $action): RedirectResponse
    {
        $request->validate(['existingReviewerId' => ['nullable', 'integer']]);
        $name = trim((string) $request->input('reviewerName'));
        $email = trim((string) $request->input('reviewerEmail'));

        // Évaluateur existant sélectionné : on récupère nom + e-mail.
        if ($request->filled('existingReviewerId')) {
            $reviewer = Participant::where('event_id', $submission->event_id)
                ->where('role', 'reviewer')
                ->find($request->input('existingReviewerId'));
            if (! $reviewer) {
                return back()->withInput()->withErrors(['existingReviewerId' => 'Évaluateur introuvable.']);
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

        // Invitation par e-mail, soumise à la préférence de l’évaluateur.
        if (Notifier::emailAllowed($review->reviewer->user, 'review_assigned')) {
            Mail::to($email)->send(new ReviewerInvitationMail($review->reviewer, review: $review));
        }

        return back()->with('ukc_status', 'Évaluateur assigné et invité par e-mail.');
    }

    /**
     * Retire une assignation. ⚠️ On n'efface pas la ligne : une évaluation est une
     * pièce du dossier (qui a été sollicité, quand, et ce qu'il a éventuellement
     * rendu). Elle passe « annulée » - hors des compteurs et de la couverture
     * (cf. Review::ACTIVE_STATUSES), tout en restant conservée en base.
     */
    public function removeReview(Submission $submission, Review $review): RedirectResponse
    {
        if ($review->submission_id !== $submission->id || $review->status === 'cancelled') {
            return back()->with('ukc_status', 'Assignation retirée.');
        }

        $wasCompleted = $review->isCompleted();
        $review->update(['status' => 'cancelled']);

        // L’évaluateur doit savoir qu'il n'a plus cette évaluation à rendre.
        $review->loadMissing('reviewer.user');
        Notifier::account(
            $review->reviewer?->user,
            kind: 'review_assigned',
            title: 'Évaluation annulée',
            body: 'Une évaluation qui vous avait été confiée a été retirée par le comité.',
            url: route('account.reviews'),
            icon: 'inbox',
        );

        return back()->with('ukc_status', $wasCompleted
            ? 'Assignation annulée : l’évaluation ne compte plus et reste conservée dans le dossier.'
            : 'Assignation annulée : la place est libérée pour un autre évaluateur.');
    }

    /**
     * Attribution automatique des évaluateurs (aperçu) : sélectionne un événement,
     * règle la cible et le plafond, propose un plan (matching axe + COI + charge).
     * L'expertise des évaluateurs se règle dans l'onglet « Évaluateurs » de la fiche
     * d'événement.
     */
    public function autoAssign(Request $request, AutoAssignReviewers $engine): View
    {
        $events = $this->accessibleEditions($request, 'submissions_list');
        $editionId = $request->has('event_id')
            ? (string) $request->query('event_id', '')
            : $this->currentEditionId($events);
        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;

        // ⚠️ Les paramètres du moteur sont des RÈGLES de l'événement (onglet
        // Évaluation), plus des paramètres d'URL : le plan proposé ne dépend donc
        // pas de la façon dont on est arrivé sur la page.
        $target = ReviewGrid::target($event);
        $maxLoad = ReviewGrid::maxLoad($event);

        $preview = $event ? $engine->preview($event, $target, $maxLoad) : null;

        // Plan proposé = tableau trié + paginé CÔTÉ SERVEUR (comme les autres
        // listes du back-office). ⚠️ « Appliquer » ne soumet que la page affichée :
        // on valide le plan page par page, la sélection ne traverse pas les pages.
        $sort = ListSort::resolve($request, ['ref', 'title', 'track', 'reviewer', 'coi'], 'ref', 'asc');
        $proposalsPage = null;
        if ($preview && ! empty($preview['proposals'])) {
            $keys = [
                'ref' => fn (array $p) => $p['submission_ref'] ?? '',
                'title' => fn (array $p) => mb_strtolower((string) ($p['submission_title'] ?? '')),
                'track' => fn (array $p) => mb_strtolower((string) ($p['track'] ?? '')),
                'reviewer' => fn (array $p) => mb_strtolower((string) ($p['reviewer_name'] ?? '')),
                'coi' => fn (array $p) => (string) ($p['coi'] ?? ''),
            ];
            $sorted = collect($preview['proposals'])
                ->sortBy($keys[$sort['sort']], SORT_REGULAR, $sort['dir'] === 'desc')
                ->values();
            $page = max(1, (int) Paginator::resolveCurrentPage());
            $proposalsPage = new LengthAwarePaginator(
                $sorted->slice(($page - 1) * 20, 20)->values(),
                $sorted->count(),
                20,
                $page,
                ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()],
            );
        }

        return view('admin.submissions.auto-assign', [
            'events' => $events,
            'event' => $event,
            'event_id' => $editionId,
            'target' => $target,
            'maxLoad' => $maxLoad,
            'preview' => $preview,
            'proposalsPage' => $proposalsPage,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
        ]);
    }

    /**
     * Onglet « Évaluateurs & expertise » : la matrice des axes que chaque
     * évaluateur peut évaluer, et sa charge. Elle vivait dans la fiche d'événement,
     * loin du moteur qui la consomme et de la page où l'on constate qu'un axe
     * n'est couvert par personne.
     */
    public function reviewers(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'submissions_list');
        $editionId = $request->has('event_id')
            ? (string) $request->query('event_id', '')
            : $this->currentEditionId($events);
        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;

        $reviewers = ReviewerExpertise::forEdition($event);
        $axes = ReviewerExpertise::axes($event);

        // Couverture : un axe que personne ne peut évaluer est le vrai signal de
        // cette page - sans lui, l'attribution retombera sur « hors axe ».
        $uncovered = array_values(array_filter(
            $axes,
            fn (string $axis) => ! $reviewers->contains(
                fn (array $r) => ReviewerExpertise::covers($r['tracks'], $axis)
            )
        ));

        return view('admin.submissions.reviewers', [
            'events' => $events,
            'event' => $event,
            'event_id' => $editionId,
            'reviewers' => $reviewers,
            'pendingApplications' => $event ? ReviewerApplication::with('user')->where('event_id', $event->id)->where('status', 'pending')->oldest()->get() : collect(),
            'reviewerAxes' => $axes,
            'uncoveredAxes' => $uncovered,
            'avgLoad' => $reviewers->isEmpty() ? 0.0 : round($reviewers->avg('load'), 1),
            'canWrite' => $this->canWriteEdition($event),
        ]);
    }

    /** Ajoute un évaluateur au vivier de l'événement, sans lui attribuer de soumission. */
    public function storeReviewer(Request $request, Event $event, RegisterEventReviewer $action): RedirectResponse
    {
        $data = $request->validate([
            'reviewerFirstName' => ['required', 'string', 'max:150'],
            'reviewerLastName' => ['required', 'string', 'max:150'],
            'reviewerEmail' => ['required', 'email', 'max:200'],
            'reviewerUserId' => ['nullable', 'integer', 'exists:users,id'],
            'reviewerInstitution' => ['nullable', 'string', 'max:255'],
            'reviewerTracks' => ['array'],
            'reviewerTracks.*' => ['string', Rule::in(ReviewerExpertise::axes($event))],
        ]);

        $selectedAccount = isset($data['reviewerUserId']) ? User::find($data['reviewerUserId']) : null;
        if ($selectedAccount && mb_strtolower((string) $selectedAccount->email) !== mb_strtolower(trim($data['reviewerEmail']))) {
            throw ValidationException::withMessages(['reviewerEmail' => 'Sélectionnez à nouveau le compte correspondant à cet e-mail.']);
        }

        // L'ajout manuel d'un candidat vaut acceptation de sa demande.
        $account = $selectedAccount ?? User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['reviewerEmail']))])->first();
        [$acceptedApplication, $reviewer] = DB::transaction(function () use ($action, $event, $data, $selectedAccount, $account): array {
            $application = $account
                ? ReviewerApplication::where('event_id', $event->id)->where('user_id', $account->id)->where('status', 'pending')->lockForUpdate()->first()
                : null;
            $reviewer = $action->handle(
                $event,
                trim($data['reviewerFirstName']),
                trim($data['reviewerLastName']),
                $data['reviewerEmail'],
                trim($data['reviewerInstitution'] ?? ''),
                $data['reviewerTracks'] ?? [],
                $selectedAccount,
            );
            if ($application) {
                $this->markReviewerApplicationAccepted($application);
            }

            return [$application, $reviewer];
        });
        if ($acceptedApplication) {
            $mailSent = $this->notifyReviewerApplicationAccepted($acceptedApplication, $reviewer);
        } else {
            $mailSent = $this->sendReviewerRegistrationMail($reviewer);
        }

        $redirect = redirect()->route('admin.submissions.reviewers', ['event_id' => $event->id]);

        return $mailSent
            ? $redirect->with('ukc_status', 'Évaluateur ajouté à cet événement. Il est disponible pour les attributions.')
            : $redirect->with('ukc_error', 'Évaluateur ajouté à cet événement, mais son e-mail n’a pas été envoyé. Vérifiez son adresse et les réglages SMTP.');
    }

    public function acceptReviewerApplication(Event $event, ReviewerApplication $application, RegisterEventReviewer $action): RedirectResponse
    {
        abort_unless($application->event_id === $event->id, 404);

        $acceptedReviewer = DB::transaction(function () use ($event, $application, $action): ?Participant {
            $application = ReviewerApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($application->status !== 'pending') {
                return null;
            }

            $account = $application->user;
            abort_if($account === null, 422, 'Le compte de ce candidat n’est plus disponible.');
            $participant = Participant::where('event_id', $event->id)->where('user_id', $account->id)->first();
            $reviewer = $action->handle(
                $event,
                (string) ($account->first_name ?: $participant?->first_name ?: $account->name),
                (string) ($account->last_name ?: $participant?->last_name ?: ''),
                (string) $account->email,
                (string) ($participant?->institution ?: $account->affiliation ?: ''),
                [],
                $account,
            );
            $this->markReviewerApplicationAccepted($application);

            return $reviewer;
        });
        $redirect = redirect()->route('admin.submissions.reviewers', ['event_id' => $event->id]);
        if (! $acceptedReviewer) {
            return $redirect->with('ukc_status', 'Cette candidature a déjà été traitée.');
        }

        $mailSent = $this->notifyReviewerApplicationAccepted($application, $acceptedReviewer);

        return ! $mailSent
            ? $redirect->with('ukc_error', 'Candidature acceptée, mais l’e-mail de confirmation n’a pas été envoyé. Vérifiez l’adresse du participant et les réglages SMTP.')
            : $redirect->with('ukc_status', 'Candidature acceptée. Le participant a été informé.');
    }

    public function resendReviewerRegistration(Event $event, Participant $reviewer): RedirectResponse
    {
        abort_unless($reviewer->event_id === $event->id && $reviewer->role === 'reviewer', 404);

        $mailSent = $this->sendReviewerRegistrationMail($reviewer);
        $redirect = redirect()->route('admin.submissions.reviewers', ['event_id' => $event->id]);

        return $mailSent
            ? $redirect->with('ukc_status', 'E-mail de l’évaluateur envoyé.')
            : $redirect->with('ukc_error', 'E-mail non envoyé. Vérifiez l’adresse de l’évaluateur et les réglages SMTP.');
    }

    public function removeReviewer(Event $event, Participant $reviewer): RedirectResponse
    {
        abort_unless($reviewer->event_id === $event->id, 404);

        $result = DB::transaction(function () use ($event, $reviewer): ?array {
            $reviewer = Participant::where('event_id', $event->id)->lockForUpdate()->findOrFail($reviewer->id);
            if ($reviewer->role !== 'reviewer') {
                return null;
            }

            // Les travaux non rendus sont annulés ; les évaluations terminées,
            // refusées et annulées restent rattachées à la fiche historique.
            $cancelled = Review::where('reviewer_id', $reviewer->id)
                ->whereIn('status', Review::PENDING_STATUSES)
                ->update(['status' => 'cancelled']);

            $revoked = Attestation::where('event_id', $event->id)
                ->where('participant_id', $reviewer->id)
                ->where('type', 'instruction_resumes')
                ->where('revoked', false)
                ->update(['revoked' => true]);

            $previousRole = ($reviewer->meta ?? [])['reviewer_previous_role'] ?? null;
            if ($revoked > 0) {
                $meta = $reviewer->meta ?? [];
                $meta['instruction_attestation_revoked_with_reviewer'] = true;
                $reviewer->meta = $meta;
            }
            $reviewer->role = in_array($previousRole, ['author', 'coauthor', 'attendee'], true)
                ? $previousRole
                : 'former_reviewer';
            $reviewer->save();

            return [$reviewer, $cancelled];
        });

        $redirect = redirect()->route('admin.submissions.reviewers', ['event_id' => $event->id]);
        if ($result === null) {
            return $redirect->with('ukc_status', 'Cette personne ne figure déjà plus parmi les évaluateurs.');
        }

        [$reviewer, $cancelled] = $result;
        if ($cancelled > 0) {
            Notifier::account(
                $reviewer->user,
                'review_assigned',
                'Évaluations annulées',
                'Votre rôle d’évaluateur pour « '.$event->title.' » a été retiré. Vos évaluations en attente ont été annulées.',
                route('account.dashboard'),
                'inbox',
            );
        }

        return $redirect->with('ukc_status', 'Évaluateur retiré de cet événement.'.($cancelled > 0 ? ' '.$cancelled.' affectation(s) en attente annulée(s).' : ''));
    }

    private function markReviewerApplicationAccepted(ReviewerApplication $application): void
    {
        $application->update([
            'status' => 'accepted',
            'approved_by' => Auth::guard('web')->id(),
            'approved_at' => now(),
        ]);
    }

    private function notifyReviewerApplicationAccepted(ReviewerApplication $application, Participant $reviewer): bool
    {
        Notifier::accountNow(
            $application->user,
            'reviewer_application_accepted',
            'Candidature d’évaluateur acceptée',
            'Votre candidature pour « '.$application->event->title.' » est acceptée. Vous pouvez désormais être sollicité pour des évaluations.',
            route('account.dashboard'),
            'check-circle',
        );

        return $this->sendReviewerRegistrationMail($reviewer, fromApplication: true);
    }

    private function sendReviewerRegistrationMail(Participant $reviewer, bool $fromApplication = false): bool
    {
        try {
            Mail::to($reviewer->email)->send(new ReviewerRegistrationMail($reviewer, $fromApplication));

            return true;
        } catch (TransportExceptionInterface $exception) {
            Log::warning('E-mail d’inscription de l’évaluateur non envoyé.', [
                'event_id' => $reviewer->event_id,
                'reviewer_id' => $reviewer->id,
                'recipient' => $reviewer->email,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /** Suggestions de comptes pour le panneau d'ajout d'un évaluateur. */
    public function reviewerPeople(Request $request, Event $event): JsonResponse
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $users = UserDirectorySearch::find($data['q'] ?? '', $event);

        $existingEmails = Participant::where('event_id', $event->id)
            ->where('role', 'reviewer')
            ->pluck('email')
            ->map(fn ($email) => mb_strtolower((string) $email))
            ->all();

        return response()->json(['people' => $users->map(function (User $user) use ($existingEmails) {
            $firstName = trim((string) $user->first_name);
            $lastName = trim((string) $user->last_name);
            if ($firstName === '' && $lastName === '' && ! str_contains((string) $user->name, '@')) {
                $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
                $lastName = count($parts) > 1 ? (string) array_pop($parts) : '';
                $firstName = implode(' ', $parts);
            }

            return [
                'id' => $user->id,
                'name' => trim($firstName.' '.$lastName) ?: $user->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => (string) $user->email,
                'institution' => (string) ($user->affiliation ?? ''),
                'already_registered' => in_array(mb_strtolower((string) $user->email), $existingEmails, true),
            ];
        })->values()]);
    }

    /**
     * Onglet « Options » : toutes les RÈGLES du module Soumissions pour cette
     * événement - dépôt (limites du formulaire), attribution (évaluateurs visés,
     * plafond de charge) et évaluation (grille, délai). Elles étaient éclatées
     * entre la page d'attribution (en paramètres d'URL), la fiche d'événement et
     * l'éditeur de formulaire.
     */
    public function options(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'submissions_list');
        $editionId = $request->has('event_id')
            ? (string) $request->query('event_id', '')
            : $this->currentEditionId($events);
        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;

        return view('admin.submissions.options', [
            'events' => $events,
            'event' => $event,
            'event_id' => $editionId,
            'reviewCriteria' => ReviewGrid::criteria($event),
            'reviewScale' => ReviewGrid::SCALE,
            'reviewDueDays' => ReviewGrid::dueDays($event),
            'target' => ReviewGrid::target($event),
            'maxLoad' => ReviewGrid::maxLoad($event),
            'volunteeringEnabled' => ReviewerVolunteering::enabled($event),
            'volunteeringGrades' => ReviewerVolunteering::grades($event),
            'participantGrades' => ParticipantTypes::all(),
            'formRules' => (new SubmissionFormConfig($event ?? new Event))->rules(),
            'canWrite' => $this->canWriteEdition($event),
        ]);
    }

    /**
     * Ces deux onglets vivent dans le module Soumissions (capacité
     * `submissions_list`) mais écrivent dans les réglages de l'ÉDITION : sans ce
     * contrôle, on afficherait des formulaires qui finiraient en 403.
     */
    private function canWriteEdition(?Event $event): bool
    {
        return $event !== null && in_array(
            $event->id,
            Auth::guard('web')->user()?->accessibleEditionIds('editions_write') ?? [],
            true,
        );
    }

    /** Applique le plan validé (paires soumission→évaluateur cochées). */
    public function autoAssignApply(Request $request, AutoAssignReviewers $engine): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'pairs' => ['required', 'array', 'min:1'],
            'pairs.*' => ['string'],
            // Justification proposée par le moteur, archivée sur l’évaluation.
            'reasons' => ['array'],
            'reasons.*' => ['nullable', 'string', 'max:200'],
        ]);

        $event = Event::findOrFail($data['event_id']);

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

        $created = $engine->apply($event, $pairs);

        return redirect()
            ->route('admin.submissions.auto-assign', ['event_id' => $event->id])
            ->with('ukc_status', $created > 0
                ? $created.' évaluateur(s) assigné(s) et invité(s) par e-mail.'
                : 'Aucune nouvelle assignation (déjà en place ou conflit d’intérêt).');
    }

    /** Même règle pour la liste des évaluateurs, les conflits et l'historique. */
    private static function visibleReview(Review $review): bool
    {
        return $review->status !== 'cancelled'
            && ($review->status === 'completed' || $review->reviewer?->role === 'reviewer');
    }

    /**
     * Historique dérivé des vrais événements (dépôt, versions, évaluations).
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
            // L'archive de v1 est créée AU MOMENT où v2 arrive : l'événement
            // à cette date est donc le renvoi de v2, et non celui de v1.
            $events[] = ['when' => $v->created_at, 'what' => 'Version '.((int) $v->version + 1).' resoumise', 'done' => false];
        }

        foreach ($reviews as $review) {
            if (! self::visibleReview($review)) {
                continue;
            }

            $name = $review->reviewer?->full_name ?: 'un évaluateur';
            $events[] = ['when' => $review->created_at, 'what' => 'Assignée à '.$name, 'done' => false];
            if ($review->isCompleted()) {
                $reco = $review->recommendationLabel();
                $events[] = ['when' => $review->updated_at, 'what' => 'Évaluation de '.$name.' rendue'.($reco ? ' - '.$reco : ''), 'done' => true];
            }
        }

        return collect($events)->sortBy(fn (array $e) => $e['when']?->getTimestamp() ?? 0)->values()->all();
    }

    /** L'auteur correspondant (participant lié) a-t-il un paiement réglé sur l'événement ? */
    private function authorHasPaid(Submission $submission): bool
    {
        return $submission->participant?->participationPayments()->where('status', 'paid')->exists() ?? false;
    }
}
