<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use App\Models\ProjectTaskDocument;
use App\Models\User;
use App\Support\Notifier;
use App\Support\TaskDocSpace;
use App\Support\XlsxWriter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gestion de projet d'un événement : chaque événement est pilotée comme un projet
 * (tâches façon kanban + jalons du cycle de vie). Contrôleur + Blade, briques
 * Tailwise (cards, tabs, progress, slideover, tom-select, datepicker).
 */
class ProjectController extends Controller
{
    use Concerns\ScopesEditionAccess;

    /**
     * Relations qu'une carte de tâche a besoin de lire.
     *
     * ⚠️ Une seule liste : la carte est construite depuis TROIS endroits
     * (création, modification, chargement initial) et un chargement oublié ne se
     * voit pas - il fait seulement N requêtes de plus, ou rend une liste de
     * collaborateurs vide.
     *
     * @var list<string>
     */
    private const RELATIONS_CARTE = ['assignee', 'collaborators', 'documents'];

    /** Liens contextuels tâche → feature (libellé). L'URL est bâtie par linkUrl(). */
    public const LINK_TARGETS = [
        'submissions' => 'Soumissions',
        'reviewers' => 'Évaluateurs / attribution',
        'committees' => 'Comités',
        'participants' => 'Participants',
        'payments' => 'Paiements',
        'documents' => 'Documents',
        'emails' => 'E-mails',
        'program' => 'Programme',
        'reports' => 'Rapports',
        'event' => 'Fiche de l’événement',
    ];

    public function index(Request $request, TaskDocSpace $docspace): View
    {
        $event = $this->currentEdition($request);
        $user = $request->user();

        // État initial pour le client (Alpine) - tout le CRUD et le calendrier
        // sont ensuite pilotés en direct depuis cet état (AJAX, sans rechargement).
        $payload = $event ? $this->editionPayload($event) : ['tasks' => [], 'milestones' => []];

        return view('admin.project.index', [
            'events' => $this->accessibleEditions($request, 'project'),
            'event' => $event,
            'event_id' => $event ? (string) $event->id : '',
            'tasksPayload' => $payload['tasks'],
            'milestonesPayload' => $payload['milestones'],
            'team' => $event ? $this->assigneePool($event, $user) : collect(),
            'statuses' => ProjectTask::STATUSES,
            'priorities' => ProjectTask::PRIORITIES,
            'linkTargets' => self::LINK_TARGETS,
            'currentUserId' => $request->user()?->id,
            'canDeleteDocuments' => $event !== null && $user !== null
                && ($user->isColloqueAdmin()
                    || array_intersect(['gerant', 'co_gerant'], $user->rolesOnEdition($event)) !== []),
            'officeAvailable' => $docspace->configured(),
        ]);
    }

    public function storeTask(Request $request): RedirectResponse|JsonResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Sélectionnez un événement.'], 422)
                : back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $data = $this->validateTask($request, $event);
        $data['event_id'] = $event->id;
        $data['created_by'] = $request->user()?->id;
        $data['position'] = (int) ProjectTask::where('event_id', $event->id)
            ->where('status', $data['status'])->max('position') + 1;
        if ($data['status'] === 'done') {
            $data['completed_at'] = now();
        }

        $collaborateurs = $data['collaborator_ids'];
        unset($data['collaborator_ids']);

        $task = ProjectTask::create($data);
        $task->collaborators()->sync($collaborateurs);
        $this->notifyAssignment($task, null, $request);
        $this->notifyCollaborators($task, [], $collaborateurs, $request);

        if ($request->expectsJson()) {
            return response()->json(['task' => $this->taskCard($task->load(self::RELATIONS_CARTE), $event)]);
        }

        return back()->with('ukc_status', 'Tâche ajoutée.');
    }

    public function updateTask(Request $request, ProjectTask $task, TaskDocSpace $docspace): RedirectResponse|JsonResponse
    {
        $event = $task->event;
        $data = $this->validateTask($request, $event);
        $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;

        $previousAssignee = $task->assignee_id;
        $avant = $task->collaborators()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $associesAvant = $task->associateIds();
        $collaborateurs = $data['collaborator_ids'];
        unset($data['collaborator_ids']);

        $task->update($data);
        $task->collaborators()->sync($collaborateurs);
        $this->notifyAssignment($task, $previousAssignee, $request);
        $this->notifyCollaborators($task, $avant, $collaborateurs, $request);
        $this->renouvelerLesLiens($task, $associesAvant, $docspace);

        if ($request->expectsJson() && $event) {
            return response()->json(['task' => $this->taskCard($task->fresh()->load(self::RELATIONS_CARTE), $event)]);
        }

        return back()->with('ukc_status', 'Tâche mise à jour.');
    }

    public function destroyTask(Request $request, ProjectTask $task, TaskDocSpace $docspace): RedirectResponse|JsonResponse
    {
        $this->oublierLesFichiers($task, $docspace);
        $task->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('ukc_status', 'Tâche supprimée.');
    }

    /** Déplacement kanban : nouvelle colonne + ordre des cartes de cette colonne. */
    public function moveTask(Request $request, ProjectTask $task): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectTask::STATUSES))],
            'order' => ['array'],
            'order.*' => ['integer'],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        // Réordonne la colonne cible d'après la liste d'ids reçue (bornée à l'événement).
        foreach (array_values($validated['order'] ?? []) as $i => $id) {
            ProjectTask::where('event_id', $task->event_id)->where('id', $id)
                ->update(['position' => $i]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('ukc_status', 'Tâche déplacée.');
    }

    /** Fil de commentaires d'une tâche (chargé à la demande par le panneau). */
    public function comments(ProjectTask $task): JsonResponse
    {
        $comments = $task->comments()->with('author')->orderByDesc('id')->get()
            ->map(fn (ProjectTaskComment $c) => [
                'id' => $c->id,
                'body' => $c->body,
                'author' => optional($c->author)->name ?? 'Utilisateur',
                'at' => $c->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json(['comments' => $comments]);
    }

    /** Ajoute un commentaire à une tâche (auteur = utilisateur connecté). */
    public function addComment(Request $request, ProjectTask $task): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()?->id,
            'body' => $data['body'],
        ])->load('author');

        return response()->json(['comment' => [
            'id' => $comment->id,
            'body' => $comment->body,
            'author' => optional($comment->author)->name ?? 'Utilisateur',
            'at' => $comment->created_at?->format('d/m/Y H:i'),
        ]]);
    }

    /** Re-planification depuis le calendrier (glisser-déposer) : échéance d'une tâche. */
    public function rescheduleTask(Request $request, ProjectTask $task): JsonResponse
    {
        $data = $request->validate(['due_date' => ['nullable', 'date']]);
        $task->update(['due_date' => $data['due_date'] ?? null]);

        $event = $task->event;

        return response()->json($event ? ['task' => $this->taskCard($task->fresh()->load('assignee'), $event)] : ['ok' => true]);
    }

    /** Re-planification depuis le calendrier (glisser-déposer) : échéance d'un jalon. */
    public function rescheduleMilestone(Request $request, ProjectMilestone $milestone): JsonResponse
    {
        $data = $request->validate(['due_date' => ['nullable', 'date']]);
        $milestone->update(['due_date' => $data['due_date'] ?? null]);

        return response()->json(['milestone' => $this->milestoneData($milestone->fresh())]);
    }

    public function storeMilestone(Request $request): RedirectResponse|JsonResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Sélectionnez un événement.'], 422)
                : back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $data = $this->validateMilestone($request);
        $data['event_id'] = $event->id;
        $data['position'] = (int) ProjectMilestone::where('event_id', $event->id)->max('position') + 1;

        $milestone = ProjectMilestone::create($data);

        if ($request->expectsJson()) {
            return response()->json(['milestone' => $this->milestoneData($milestone)]);
        }

        return back()->with('ukc_status', 'Jalon ajouté.');
    }

    public function updateMilestone(Request $request, ProjectMilestone $milestone): RedirectResponse|JsonResponse
    {
        $milestone->update($this->validateMilestone($request));

        if ($request->expectsJson()) {
            return response()->json(['milestone' => $this->milestoneData($milestone->fresh())]);
        }

        return back()->with('ukc_status', 'Jalon mis à jour.');
    }

    public function toggleMilestone(Request $request, ProjectMilestone $milestone): RedirectResponse|JsonResponse
    {
        $done = ! $milestone->isDone();
        $milestone->update([
            'status' => $done ? 'done' : 'pending',
            'done_at' => $done ? now() : null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['milestone' => $this->milestoneData($milestone->fresh())]);
        }

        return back()->with('ukc_status', $done ? 'Jalon marqué atteint.' : 'Jalon rouvert.');
    }

    public function destroyMilestone(Request $request, ProjectMilestone $milestone): RedirectResponse|JsonResponse
    {
        $milestone->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('ukc_status', 'Jalon supprimé.');
    }

    /** Amorce le projet avec le cycle de vie type d'un colloque (idempotent). */
    public function seedTemplate(Request $request): RedirectResponse|JsonResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Sélectionnez un événement.'], 422)
                : back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        // Sécurité : on n'amorce que si le projet est vierge.
        if (ProjectTask::where('event_id', $event->id)->exists()
            || ProjectMilestone::where('event_id', $event->id)->exists()) {
            return $request->expectsJson()
                ? response()->json(['error' => 'Le projet contient déjà des éléments.'], 422)
                : back()->with('ukc_error', 'Le projet contient déjà des éléments - amorçage ignoré.');
        }

        foreach ($this->templateMilestones($event) as $i => $m) {
            ProjectMilestone::create($m + ['event_id' => $event->id, 'position' => $i]);
        }

        $pos = [];
        foreach ($this->templateTasks() as $t) {
            $status = $t['status'] ?? 'todo';
            $pos[$status] = ($pos[$status] ?? -1) + 1;
            ProjectTask::create($t + [
                'event_id' => $event->id,
                'created_by' => $request->user()?->id,
                'position' => $pos[$status],
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->editionPayload($event));
        }

        return back()->with('ukc_status', 'Projet initialisé avec le cycle de vie type d’un colloque.');
    }

    /** Export Excel du projet : deux feuilles (Tâches, Jalons). */
    public function exportXlsx(Request $request): Response|RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $tasks = ProjectTask::where('event_id', $event->id)->with(self::RELATIONS_CARTE)
            ->orderBy('phase')->orderBy('status')->orderBy('position')->get();

        $taskRows = $tasks->map(fn (ProjectTask $t) => [
            $t->title,
            $t->phase ?: '-',
            $t->statusLabel(),
            $t->priorityLabel(),
            optional($t->assignee)->name ?: '-',
            $t->start_date?->format('d/m/Y') ?: '-',
            $t->due_date?->format('d/m/Y') ?: '-',
            implode('/', $t->checklistProgress()),
            $t->link_key ? (self::LINK_TARGETS[$t->link_key] ?? $t->link_key) : '-',
        ])->all();

        $milestones = ProjectMilestone::where('event_id', $event->id)
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('position')->get();

        $msRows = $milestones->map(fn (ProjectMilestone $m) => [
            $m->title,
            $m->due_date?->format('d/m/Y') ?: '-',
            $m->isDone() ? 'Atteint' : 'À venir',
            (string) $m->description,
        ])->all();

        return (new XlsxWriter)
            ->sheet('Tâches', ['Tâche', 'Phase', 'Statut', 'Priorité', 'Responsable', 'Début', 'Échéance', 'Checklist', 'Lien'], $taskRows)
            ->sheet('Jalons', ['Jalon', 'Échéance', 'Statut', 'Description'], $msRows)
            ->download('projet-'.($event->slug ?: $event->id).'.xlsx');
    }

    /** Rapport du projet (page imprimable → PDF) : synthèse d'avancement. */
    public function report(Request $request): View|RedirectResponse
    {
        $event = $this->currentEdition($request);
        if (! $event) {
            return back()->with('ukc_error', 'Sélectionnez un événement.');
        }

        $tasks = ProjectTask::where('event_id', $event->id)->with('assignee')
            ->orderBy('position')->orderBy('id')->get();
        $milestones = ProjectMilestone::where('event_id', $event->id)
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('position')->get();

        $total = $tasks->count();
        $done = $tasks->where('status', 'done')->count();

        $byStatus = [];
        foreach (ProjectTask::STATUSES as $key => $label) {
            $byStatus[] = ['label' => $label, 'count' => $tasks->where('status', $key)->count()];
        }

        $byPhase = $tasks->groupBy(fn (ProjectTask $t) => $t->phase ?: 'Sans phase')
            ->map(fn ($g) => ['total' => $g->count(), 'done' => $g->where('status', 'done')->count()]);

        return view('admin.project.report', [
            'event' => $event,
            'tasks' => $tasks,
            'milestones' => $milestones,
            'total' => $total,
            'done' => $done,
            'progress' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'overdue' => $tasks->filter(fn (ProjectTask $t) => $t->isOverdue())->values(),
            'byStatus' => $byStatus,
            'byPhase' => $byPhase,
            'msDone' => $milestones->where('status', 'done')->count(),
        ]);
    }

    /**
     * Jalons standard, datés à partir des dates clés de l'événement quand elles
     * existent (clôture des soumissions, ouverture/clôture, dates libres).
     *
     * @return array<int, array<string, mixed>>
     */
    private function templateMilestones(Event $event): array
    {
        $deadline = $event->submission_deadline ? Carbon::parse($event->submission_deadline)->toDateString() : null;
        $start = $event->date_start?->toDateString();
        $end = $event->date_end?->toDateString();

        $milestones = [
            ['title' => 'Ouverture de l’appel à communications', 'due_date' => null],
            ['title' => 'Clôture des soumissions', 'due_date' => $deadline],
            ['title' => 'Notifications aux auteurs', 'due_date' => null],
            ['title' => 'Réception des versions finales', 'due_date' => null],
            ['title' => 'Publication du programme', 'due_date' => null],
            ['title' => 'Ouverture du colloque', 'due_date' => $start],
            ['title' => 'Clôture du colloque', 'due_date' => $end],
            ['title' => 'Publication des actes', 'due_date' => null],
        ];

        // Dates clés libres saisies sur l'événement (settings['keyDates']).
        foreach ((array) ($event->settings['keyDates'] ?? []) as $kd) {
            $label = trim((string) ($kd['label'] ?? ''));
            $date = trim((string) ($kd['date'] ?? ''));
            if ($label !== '') {
                $milestones[] = ['title' => $label, 'due_date' => $date !== '' ? $date : null];
            }
        }

        return $milestones;
    }

    /**
     * Tâches modèles regroupées par phase du cycle de vie.
     *
     * @return array<int, array<string, mixed>>
     */
    private function templateTasks(): array
    {
        $t = fn (string $phase, string $title, string $priority = 'normal', ?string $link_key = null) => compact('phase', 'title', 'priority', 'link_key');

        return [
            $t('Cadrage', 'Définir le thème et les axes thématiques', 'high', 'event'),
            $t('Cadrage', 'Constituer les comités (scientifique & organisation)', 'high', 'committees'),
            $t('Cadrage', 'Établir le budget prévisionnel'),
            $t('Appel à communications', 'Rédiger et diffuser l’appel à communications', 'high', 'emails'),
            $t('Appel à communications', 'Mettre en ligne le site de l’événement', 'normal', 'event'),
            $t('Soumissions & évaluation', 'Configurer le formulaire de soumission', 'normal', 'submissions'),
            $t('Soumissions & évaluation', 'Recruter et affecter les évaluateurs', 'normal', 'reviewers'),
            $t('Soumissions & évaluation', 'Collecter les évaluations et arrêter les décisions', 'normal', 'submissions'),
            $t('Programme', 'Composer le programme (sessions, ateliers)', 'normal', 'program'),
            $t('Programme', 'Planifier les créneaux et les salles', 'normal', 'program'),
            $t('Logistique', 'Gérer les inscriptions et les paiements', 'normal', 'payments'),
            $t('Logistique', 'Organiser l’accueil, les badges et la signalétique', 'normal', 'documents'),
            $t('Tenue', 'Imprimer les badges et le livre des résumés', 'normal', 'documents'),
            $t('Tenue', 'Coordonner l’accueil et le pointage sur place', 'normal', 'participants'),
            $t('Après-colloque', 'Émettre les attestations', 'normal', 'documents'),
            $t('Après-colloque', 'Compiler et publier les actes', 'normal', 'documents'),
            $t('Après-colloque', 'Faire le bilan et remercier les parties prenantes'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, ?Event $event): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectTask::STATUSES))],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectTask::PRIORITIES))],
            'phase' => ['nullable', 'string', 'max:60'],
            'link_key' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::LINK_TARGETS))],
            'assignee_id' => ['nullable', 'integer'],
            'collaborator_ids' => ['nullable', 'array', 'max:30'],
            'collaborator_ids.*' => ['integer'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'checklist_json' => ['nullable', 'string'],
        ]);

        // Le responsable doit appartenir au vivier de l'événement (sinon on ignore).
        $vivier = $event ? $this->assigneePool($event, $request->user()) : null;

        if (! empty($validated['assignee_id']) && $vivier
            && ! $vivier->contains('id', (int) $validated['assignee_id'])) {
            $validated['assignee_id'] = null;
        }

        // ⚠️ **Les collaborateurs se bornent au MÊME vivier que le responsable.**
        // Ils ouvrent le document de travail de la tâche : y laisser entrer un
        // identifiant quelconque donnerait accès au plan de travail d'un
        // événement à quelqu'un qui n'y est pas affecté. Un identifiant posté
        // n'est pas une autorisation.
        $validated['collaborator_ids'] = collect($validated['collaborator_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $vivier !== null && $vivier->contains('id', $id))
            ->unique()
            ->values()
            ->all();

        // Checklist : JSON envoyé par le panneau, assaini en [{text, done}].
        $validated['checklist'] = $this->parseChecklist($validated['checklist_json'] ?? null);
        unset($validated['checklist_json']);

        return $validated;
    }

    /**
     * Assainit la checklist reçue en JSON → tableau borné de [{text, done}].
     *
     * @return array<int, array{text: string, done: bool}>
     */
    private function parseChecklist(?string $json): array
    {
        if (! is_string($json) || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach (array_slice($decoded, 0, 50) as $row) {
            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $items[] = ['text' => mb_substr($text, 0, 200), 'done' => (bool) ($row['done'] ?? false)];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMilestone(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,done'],
        ]);
    }

    /** URL de la feature liée à une tâche, filtrée sur l'événement (ou null). */
    private function linkUrl(?string $key, Event $event): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }

        $q = ['event_id' => $event->id];

        return match ($key) {
            'submissions' => route('admin.submissions.index', $q),
            'participants' => route('admin.participants.index', $q),
            'payments' => route('admin.payments.index', $q),
            'documents' => route('admin.documents.attestations', $q),
            'emails' => route('admin.emails.index', $q),
            'program' => route('admin.program.slots', $q),
            'reports' => route('admin.reports.index', $q),
            'reviewers' => route('admin.submissions.reviewers', $q),
            'committees' => route('admin.evenements.edit', ['event' => $event, 'tab' => 'committees']),
            'event' => route('admin.evenements.edit', ['event' => $event]),
            default => null,
        };
    }

    /**
     * Charge utile d'une tâche pour l'état client (Alpine) - même forme partout.
     *
     * @return array<string, mixed>
     */
    private function taskCard(ProjectTask $task, Event $event): array
    {
        [$checkDone, $checkTotal] = $task->checklistProgress();

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => (string) $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'priorityLabel' => $task->priorityLabel(),
            'phase' => (string) $task->phase,
            'start_date' => $task->start_date?->toDateString(),
            'assignee_id' => $task->assignee_id,
            'assignee' => optional($task->assignee)->name,
            'collaborator_ids' => $task->collaborators->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'collaborators' => $task->collaborators->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->salutationName() ?: $u->name,
            ])->values()->all(),
            // Documents de travail rédigés à plusieurs sur cette tâche.
            'documents' => $task->documents
                ->sortBy('id')
                ->map(fn (ProjectTaskDocument $d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'format' => $d->format,
                    'url' => route('admin.project.documents.edit', [$task, $d]),
                    'deleteUrl' => route('admin.project.documents.destroy', [$task, $d]),
                    'savedLabel' => $d->saved_at?->format('d/m/Y H:i'),
                ])->values()->all(),
            'documentsUrl' => route('admin.project.documents.store', $task),
            'due_date' => $task->due_date?->toDateString(),
            'dueLabel' => $task->due_date?->format('d/m/Y'),
            'overdue' => $task->isOverdue(),
            'position' => $task->position,
            'checklist' => is_array($task->checklist) ? $task->checklist : [],
            'checkDone' => $checkDone,
            'checkTotal' => $checkTotal,
            'link_key' => (string) $task->link_key,
            'linkUrl' => $this->linkUrl($task->link_key, $event),
            'linkLabel' => $task->link_key ? (self::LINK_TARGETS[$task->link_key] ?? null) : null,
        ];
    }

    /**
     * Charge utile d'un jalon pour l'état client.
     *
     * @return array<string, mixed>
     */
    private function milestoneData(ProjectMilestone $m): array
    {
        return [
            'id' => $m->id,
            'title' => $m->title,
            'description' => (string) $m->description,
            'due_date' => $m->due_date?->toDateString(),
            'dueLabel' => $m->due_date?->format('d/m/Y'),
            'status' => $m->status,
            'done' => $m->isDone(),
            'overdue' => $m->isOverdue(),
        ];
    }

    /**
     * Tâches + jalons d'un événement, mis en forme pour l'état client.
     *
     * @return array{tasks: array<int, array<string, mixed>>, milestones: array<int, array<string, mixed>>}
     */
    private function editionPayload(Event $event): array
    {
        $tasks = ProjectTask::where('event_id', $event->id)->with('assignee')
            ->orderBy('position')->orderBy('id')->get()
            ->map(fn (ProjectTask $t) => $this->taskCard($t, $event))->all();

        $milestones = ProjectMilestone::where('event_id', $event->id)
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('position')->get()
            ->map(fn (ProjectMilestone $m) => $this->milestoneData($m))->all();

        return ['tasks' => $tasks, 'milestones' => $milestones];
    }

    /**
     * Notifie le responsable qu'une tâche lui a été assignée (in-app + e-mail
     * selon ses préférences). Silencieux si pas de (nouveau) responsable, si
     * inchangé, ou si l'on s'assigne soi-même.
     */
    private function notifyAssignment(ProjectTask $task, ?int $previousAssigneeId, Request $request): void
    {
        $newId = $task->assignee_id;
        if (! $newId || $newId === $previousAssigneeId || $newId === $request->user()?->id) {
            return;
        }

        $assignee = $task->assignee()->first();
        if (! $assignee) {
            return;
        }

        $event = $task->event;
        Notifier::user(
            $assignee,
            kind: 'task_assigned',
            title: 'Une tâche vous a été assignée',
            body: '« '.$task->title.' » - '.($event?->title ?: 'colloque')
                .($task->due_date ? ' - échéance '.$task->due_date->format('d/m/Y') : ''),
            url: $event ? route('admin.project.index', ['event_id' => $event->id]) : null,
            icon: 'layers',
        );
    }

    /**
     * Prévient les personnes qu'on vient d'ASSOCIER à une tâche.
     *
     * ⚠️ Seulement les NOUVELLES, et jamais soi-même : réenregistrer une tâche
     * pour en corriger le titre renverrait sinon une notification à toute
     * l'équipe, à chaque fois - et des notifications qu'on apprend à ignorer ne
     * préviennent plus de rien.
     *
     * @param  list<int>  $avant
     * @param  list<int>  $apres
     */
    private function notifyCollaborators(ProjectTask $task, array $avant, array $apres, Request $request): void
    {
        $nouveaux = array_diff($apres, $avant, [(int) $request->user()?->id]);
        if ($nouveaux === []) {
            return;
        }

        $event = $task->event;

        foreach (User::whereIn('id', $nouveaux)->get() as $membre) {
            Notifier::user(
                $membre,
                kind: 'task_assigned',
                title: 'Vous êtes associé à une tâche',
                body: '« '.$task->title.' » - '.($event?->title ?: 'colloque')
                    .($task->due_date ? ' - échéance '.$task->due_date->format('d/m/Y') : ''),
                url: $event ? route('admin.project.index', ['event_id' => $event->id]) : null,
                icon: 'layers',
            );
        }
    }

    /**
     * Renouveler le lien d'édition des documents de la tâche quand quelqu'un
     * cesse d'y être associé.
     *
     * ⚠️⚠️ **RETIRER QUELQU'UN D'UNE TÂCHE NE LUI RETIRAIT PAS LE FICHIER.** Le
     * jeton du lien DocSpace est partagé par les rédacteurs et vaut un droit
     * d'écriture **hors de GEVES** : sans renouvellement, la personne écartée
     * gardait le document ouvert, et la seule parade existait sur le papier.
     *
     * ⚠️ **C'est fait DANS la requête, pas en file.** Un renouvellement confié à
     * une file qui ne tourne pas laisserait l'ancien jeton vivant sans que
     * personne ne le sache - exactement le défaut qu'on vient de fermer. Le prix
     * est l'attente au retrait d'un collaborateur, bornée par
     * `TaskDocumentController::MAX_PAR_TACHE`.
     *
     * ⚠️ Au mieux : un échec est journalisé et n'arrête pas l'enregistrement de
     * la tâche. La trace nomme le document, pour qu'on renouvelle à la main.
     *
     * @param  list<int>  $associesAvant
     */
    private function renouvelerLesLiens(ProjectTask $task, array $associesAvant, TaskDocSpace $docspace): void
    {
        $partis = array_diff($associesAvant, $task->fresh()?->associateIds() ?? []);
        if ($partis === [] || ! $docspace->configured() || ! $docspace->fileLinksEnabled()) {
            return;
        }

        foreach ($task->documents()->whereNotNull('docspace_file_id')->get() as $document) {
            try {
                $docspace->rotateFileLink($document);
            } catch (\Throwable $error) {
                Log::warning('Lien DocSpace NON renouvelé après le retrait d’un collaborateur.', [
                    'task_id' => $task->id,
                    'document_id' => $document->id,
                    'reason' => $error->getMessage(),
                ]);
            }
        }
    }

    /**
     * Supprimer une tâche emporte ses fichiers chez le prestataire.
     *
     * ⚠️⚠️ **LA CLÉ ÉTRANGÈRE EST EN CASCADE** : les lignes de documents partent
     * au niveau de la base, sans passer par le modèle. Sans ce passage, chaque
     * DOCX, son dossier **et son lien d'édition** resteraient vivants dans
     * DocSpace, sans plus rien dans GEVES pour les retrouver - donc sans plus
     * personne pour les révoquer.
     *
     * ⚠️ Au mieux, et jamais bloquant : on ne retient pas une tâche parce qu'un
     * service tiers répond mal. Mais l'incident est écrit, nommément, sinon un
     * orphelin ne se retrouve plus.
     */
    private function oublierLesFichiers(ProjectTask $task, TaskDocSpace $docspace): void
    {
        $fichiers = $task->documents()->whereNotNull('docspace_file_id')->pluck('docspace_file_id')->all();
        $dossier = (string) ($task->docspace_folder_id ?? '');

        if ($fichiers === [] && $dossier === '') {
            return;
        }

        if (! $docspace->configured()) {
            Log::warning('Tâche supprimée sans retirer ses fichiers : DocSpace n’est pas configuré.', [
                'task_id' => $task->id, 'files' => $fichiers, 'folder_id' => $dossier,
            ]);

            return;
        }

        foreach ($fichiers as $fileId) {
            try {
                $docspace->deleteFile((string) $fileId);
            } catch (\Throwable $error) {
                Log::warning('Fichier DocSpace orphelin après suppression de la tâche.', [
                    'task_id' => $task->id, 'file_id' => $fileId, 'reason' => $error->getMessage(),
                ]);
            }
        }

        if ($dossier !== '') {
            try {
                $docspace->deleteFolder($dossier);
            } catch (\Throwable $error) {
                Log::warning('Dossier DocSpace orphelin après suppression de la tâche.', [
                    'task_id' => $task->id, 'folder_id' => $dossier, 'reason' => $error->getMessage(),
                ]);
            }
        }
    }

    /**
     * Vivier des responsables et collaborateurs : les personnes explicitement
     * affectées à l'équipe de cet événement, **plus l'administrateur connecté
     * lui-même**.
     *
     * ⚠️⚠️ **Un accès global ne vaut pas affectation au projet** : dérouler
     * tous les administrateurs de la plateforme désignerait des gens qui n'y
     * travaillent pas. Mais c'est l'administrateur qui MONTE l'événement, et
     * sans lui la liste s'ouvre **vide** tant qu'aucune équipe n'existe : il ne
     * pourrait pas même se confier la première tâche. Il n'y entre donc que
     * lui, jamais ses pairs. Les deux moitiés de la règle sont sabotées dans
     * `ProjectTest`.
     *
     * @return EloquentCollection<int, User>
     */
    private function assigneePool(Event $event, ?User $viewer = null): EloquentCollection
    {
        $ids = $event->team()->pluck('users.id');

        if ($viewer && $viewer->isColloqueAdmin()) {
            $ids = $ids->push($viewer->id)->unique();
        }

        return User::query()->whereIn('id', $ids)->orderBy('name')->get();
    }

    private function currentEdition(Request $request): ?Event
    {
        $events = $this->accessibleEditions($request, 'project');
        $id = (string) $request->input('event_id', '');

        if ($id === '') {
            return $events->firstWhere('status', 'open') ?? $events->first();
        }

        return $events->firstWhere('id', (int) $id);
    }
}
