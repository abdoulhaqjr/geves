<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\ProjectTaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Gestion de projet d'une édition : chaque édition est pilotée comme un projet
 * (tâches façon kanban + jalons du cycle de vie). Contrôleur + Blade, briques
 * Tailwise (cards, tabs, progress, slideover, tom-select, datepicker).
 */
class ProjectController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $edition = $this->currentEdition($request);

        $tasks = $edition
            ? ProjectTask::where('edition_id', $edition->id)
                ->with('assignee')
                ->orderBy('position')->orderBy('id')->get()
            : collect();

        $milestones = $edition
            ? ProjectMilestone::where('edition_id', $edition->id)
                ->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('position')->get()
            : collect();

        // Regroupement des tâches par colonne kanban.
        $columns = [];
        foreach (array_keys(ProjectTask::STATUSES) as $status) {
            $columns[$status] = $tasks->where('status', $status)->values();
        }

        $total = $tasks->count();
        $done = $tasks->where('status', 'done')->count();

        $stats = [
            'total' => $total,
            'done' => $done,
            'open' => $total - $done,
            'overdue' => $tasks->filter(fn (ProjectTask $t) => $t->isOverdue())->count(),
            'progress' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'milestonesTotal' => $milestones->count(),
            'milestonesDone' => $milestones->where('status', 'done')->count(),
            'nextMilestone' => $milestones->first(fn (ProjectMilestone $m) => ! $m->isDone() && $m->due_date !== null),
        ];

        return view('admin.project.index', [
            'editions' => $this->accessibleEditions($request, 'project'),
            'edition' => $edition,
            'edition_id' => $edition ? (string) $edition->id : '',
            'columns' => $columns,
            'tasks' => $tasks,
            'milestones' => $milestones,
            'team' => $edition ? $edition->team()->orderBy('name')->get() : collect(),
            'statuses' => ProjectTask::STATUSES,
            'priorities' => ProjectTask::PRIORITIES,
            'stats' => $stats,
            'isEmpty' => $total === 0 && $milestones->isEmpty(),
        ]);
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        $data = $this->validateTask($request, $edition);
        $data['edition_id'] = $edition->id;
        $data['created_by'] = $request->user()?->id;
        $data['position'] = (int) ProjectTask::where('edition_id', $edition->id)
            ->where('status', $data['status'])->max('position') + 1;
        if ($data['status'] === 'done') {
            $data['completed_at'] = now();
        }

        ProjectTask::create($data);

        return back()->with('ukc_status', 'Tâche ajoutée.');
    }

    public function updateTask(Request $request, ProjectTask $task): RedirectResponse
    {
        $data = $this->validateTask($request, $task->edition);
        $data['completed_at'] = $data['status'] === 'done' ? ($task->completed_at ?? now()) : null;

        $task->update($data);

        return back()->with('ukc_status', 'Tâche mise à jour.');
    }

    public function destroyTask(ProjectTask $task): RedirectResponse
    {
        $task->delete();

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

        // Réordonne la colonne cible d'après la liste d'ids reçue (bornée à l'édition).
        foreach (array_values($validated['order'] ?? []) as $i => $id) {
            ProjectTask::where('edition_id', $task->edition_id)->where('id', $id)
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

        return response()->json(['ok' => true]);
    }

    /** Re-planification depuis le calendrier (glisser-déposer) : échéance d'un jalon. */
    public function rescheduleMilestone(Request $request, ProjectMilestone $milestone): JsonResponse
    {
        $data = $request->validate(['due_date' => ['nullable', 'date']]);
        $milestone->update(['due_date' => $data['due_date'] ?? null]);

        return response()->json(['ok' => true]);
    }

    public function storeMilestone(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        $data = $this->validateMilestone($request);
        $data['edition_id'] = $edition->id;
        $data['position'] = (int) ProjectMilestone::where('edition_id', $edition->id)->max('position') + 1;

        ProjectMilestone::create($data);

        return back()->with('ukc_status', 'Jalon ajouté.');
    }

    public function updateMilestone(Request $request, ProjectMilestone $milestone): RedirectResponse
    {
        $milestone->update($this->validateMilestone($request));

        return back()->with('ukc_status', 'Jalon mis à jour.');
    }

    public function toggleMilestone(ProjectMilestone $milestone): RedirectResponse
    {
        $done = ! $milestone->isDone();
        $milestone->update([
            'status' => $done ? 'done' : 'pending',
            'done_at' => $done ? now() : null,
        ]);

        return back()->with('ukc_status', $done ? 'Jalon marqué atteint.' : 'Jalon rouvert.');
    }

    public function destroyMilestone(ProjectMilestone $milestone): RedirectResponse
    {
        $milestone->delete();

        return back()->with('ukc_status', 'Jalon supprimé.');
    }

    /** Amorce le projet avec le cycle de vie type d'un colloque (idempotent). */
    public function seedTemplate(Request $request): RedirectResponse
    {
        $edition = $this->currentEdition($request);
        if (! $edition) {
            return back()->with('ukc_error', 'Sélectionnez une édition.');
        }

        // Sécurité : on n'amorce que si le projet est vierge.
        if (ProjectTask::where('edition_id', $edition->id)->exists()
            || ProjectMilestone::where('edition_id', $edition->id)->exists()) {
            return back()->with('ukc_error', 'Le projet contient déjà des éléments — amorçage ignoré.');
        }

        foreach ($this->templateMilestones($edition) as $i => $m) {
            ProjectMilestone::create($m + ['edition_id' => $edition->id, 'position' => $i]);
        }

        $pos = [];
        foreach ($this->templateTasks() as $t) {
            $status = $t['status'] ?? 'todo';
            $pos[$status] = ($pos[$status] ?? -1) + 1;
            ProjectTask::create($t + [
                'edition_id' => $edition->id,
                'created_by' => $request->user()?->id,
                'position' => $pos[$status],
            ]);
        }

        return back()->with('ukc_status', 'Projet initialisé avec le cycle de vie type d’un colloque.');
    }

    /**
     * Jalons standard, datés à partir des dates clés de l'édition quand elles
     * existent (clôture des soumissions, ouverture/clôture, dates libres).
     *
     * @return array<int, array<string, mixed>>
     */
    private function templateMilestones(Edition $edition): array
    {
        $deadline = $edition->submission_deadline ? Carbon::parse($edition->submission_deadline)->toDateString() : null;
        $start = $edition->date_start?->toDateString();
        $end = $edition->date_end?->toDateString();

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

        // Dates clés libres saisies sur l'édition (settings['keyDates']).
        foreach ((array) ($edition->settings['keyDates'] ?? []) as $kd) {
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
        $t = fn (string $phase, string $title, string $priority = 'normal') => compact('phase', 'title', 'priority');

        return [
            $t('Cadrage', 'Définir le thème et les axes thématiques', 'high'),
            $t('Cadrage', 'Constituer les comités (scientifique & organisation)', 'high'),
            $t('Cadrage', 'Établir le budget prévisionnel'),
            $t('Appel à communications', 'Rédiger et diffuser l’appel à communications', 'high'),
            $t('Appel à communications', 'Mettre en ligne le site de l’édition'),
            $t('Soumissions & relecture', 'Configurer le formulaire de soumission'),
            $t('Soumissions & relecture', 'Recruter et affecter les relecteurs'),
            $t('Soumissions & relecture', 'Collecter les avis et arrêter les décisions'),
            $t('Programme', 'Composer le programme (sessions, ateliers)'),
            $t('Programme', 'Planifier les créneaux et les salles'),
            $t('Logistique', 'Gérer les inscriptions et les paiements'),
            $t('Logistique', 'Organiser l’accueil, les badges et la signalétique'),
            $t('Tenue', 'Imprimer les badges et le livre des résumés'),
            $t('Tenue', 'Coordonner l’accueil et le pointage sur place'),
            $t('Après-colloque', 'Émettre les attestations'),
            $t('Après-colloque', 'Compiler et publier les actes'),
            $t('Après-colloque', 'Faire le bilan et remercier les parties prenantes'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request, ?Edition $edition): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectTask::STATUSES))],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys(ProjectTask::PRIORITIES))],
            'phase' => ['nullable', 'string', 'max:60'],
            'assignee_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
            'checklist_json' => ['nullable', 'string'],
        ]);

        // Le responsable doit appartenir à l'équipe de l'édition (sinon on ignore).
        if (! empty($validated['assignee_id']) && $edition
            && ! $edition->team()->where('users.id', $validated['assignee_id'])->exists()) {
            $validated['assignee_id'] = null;
        }

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

    private function currentEdition(Request $request): ?Edition
    {
        $editions = $this->accessibleEditions($request, 'project');
        $id = (string) $request->input('edition_id', '');

        if ($id === '') {
            return $editions->firstWhere('status', 'open') ?? $editions->first();
        }

        return $editions->firstWhere('id', (int) $id);
    }
}
