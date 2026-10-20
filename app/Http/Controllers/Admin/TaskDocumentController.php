<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDocument;
use App\Models\ProjectTaskDocumentVersion;
use App\Support\Realtime;
use App\Support\TaskDocumentHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Documents de travail d'une tâche de projet, rédigés À PLUSIEURS.
 *
 * ⚠️⚠️ **LE SERVEUR NE VOIT PAS PASSER LES FRAPPES.** Les modifications
 * circulent d'un navigateur à l'autre en « événements client » à travers le
 * service WebSocket : c'est ce qui donne la frappe instantanée, et c'est aussi
 * pourquoi PHP n'a rien à arbitrer en direct. Son rôle est double et il s'arrête
 * là : **autoriser** l'entrée dans le canal (`routes/channels.php`) et
 * **conserver** l'état, par instantanés espacés.
 *
 * ⚠️⚠️ **LA FUSION EST LE TRAVAIL DU CRDT, PAS DU SERVEUR.** Chaque poste tient
 * le document entier sous forme d'état Yjs ; deux états se fusionnent toujours,
 * dans n'importe quel ordre, sans perdre une frappe. C'est ce qui rend le refus
 * de concurrence ci-dessous **sûr** : un poste repoussé n'a rien perdu, il tire
 * l'état du serveur, le fusionne au sien et réessaie.
 */
class TaskDocumentController extends Controller
{
    /** Versions montrées dans le panneau d'historique. */
    private const VERSIONS_AFFICHEES = 40;

    /** Documents au plus par tâche - au-delà, c'est une bibliothèque, pas une tâche. */
    public const MAX_PAR_TACHE = 20;

    public function store(Request $request, ProjectTask $task): RedirectResponse
    {
        abort_unless($this->peutRediger($request, $task), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
        ]);

        abort_if($task->documents()->count() >= self::MAX_PAR_TACHE, 422);

        $document = $task->documents()->create([
            'title' => $data['title'],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.project.documents.edit', [$task, $document])
            ->with('ukc_status', 'Document créé.');
    }

    public function edit(Request $request, ProjectTask $task, ProjectTaskDocument $document): View
    {
        $user = $request->user();
        abort_unless($document->isVisibleTo($user), 403);

        $task->load(['event', 'assignee', 'collaborators']);

        return view('admin.project.document', [
            'task' => $task,
            'event' => $task->event,
            'document' => $document,
            'canWrite' => $document->isEditableBy($user),
            // ⚠️ `clientConfig()` ne porte JAMAIS le secret : il signe
            // l'autorisation de canal côté serveur, et nulle part ailleurs.
            'realtime' => Realtime::clientConfig(),
            'channel' => Realtime::documentChannel($document->id),
            'versions' => $this->versions($document),
            'associates' => $task->collaborators
                ->concat(array_filter([$task->assignee]))
                ->unique('id')
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->salutationName() ?: $u->name])
                ->values()
                ->all(),
        ]);
    }

    /**
     * État courant du document, pour la resynchronisation.
     *
     * ⚠️ C'est le point de reprise après un refus de concurrence : le client en
     * fusionne le contenu au sien, puis réenregistre. Sans lui, un poste repoussé
     * n'aurait d'autre issue que de recharger la page - en perdant ce qu'il
     * venait d'écrire.
     */
    public function state(Request $request, ProjectTask $task, ProjectTaskDocument $document): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403);

        return response()->json([
            'revision' => $document->revision,
            'state' => $document->state,
            'saved_at' => $document->saved_at?->toIso8601String(),
        ]);
    }

    public function save(Request $request, ProjectTask $task, ProjectTaskDocument $document): JsonResponse
    {
        abort_unless($document->isEditableBy($request->user()), 403);

        $data = $request->validate([
            // ⚠️ L'état Yjs voyage en base64 : une colonne texte passe de SQLite
            // à MySQL sans manipulation d'octets.
            'state' => ['required', 'string', 'max:8000000', 'regex:/^[A-Za-z0-9+\/=]*$/'],
            'html' => ['nullable', 'string', 'max:4000000'],
            'revision' => ['required', 'integer', 'min:0'],
        ]);

        $userId = $request->user()?->id;

        return DB::transaction(function () use ($data, $document, $userId) {
            // ⚠️ Verrou : deux instantanés qui arrivent en même temps liraient
            // sinon la même révision, et le second passerait le contrôle avec
            // un état antérieur à celui qui vient d'être écrit.
            $frais = ProjectTaskDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();

            if ((int) $data['revision'] !== (int) $frais->revision) {
                return response()->json([
                    'conflict' => true,
                    'revision' => $frais->revision,
                    'state' => $frais->state,
                ], 409);
            }

            $ouvreVersion = $frais->shouldOpenVersion();

            $frais->forceFill([
                'state' => $data['state'],
                'html' => TaskDocumentHtml::clean($data['html'] ?? ''),
                'revision' => $frais->revision + 1,
                'updated_by' => $userId,
                'saved_at' => now(),
            ])->save();

            if ($ouvreVersion) {
                $this->verserVersion($frais, $userId);
            }

            return response()->json([
                'revision' => $frais->revision,
                'saved_at' => $frais->saved_at?->toIso8601String(),
                'versions' => $ouvreVersion ? $this->versions($frais) : null,
            ]);
        });
    }

    /** Version posée à la demande (« Enregistrer une version »), avec un intitulé. */
    public function storeVersion(Request $request, ProjectTask $task, ProjectTaskDocument $document): JsonResponse
    {
        abort_unless($document->isEditableBy($request->user()), 403);

        $data = $request->validate(['label' => ['nullable', 'string', 'max:120']]);

        $this->verserVersion($document, $request->user()?->id, $data['label'] ?? null);

        return response()->json(['versions' => $this->versions($document)]);
    }

    /**
     * Contenu d'une version, pour revenir en arrière.
     *
     * ⚠️⚠️ **LE RETOUR EN ARRIÈRE SE FAIT DANS L'ÉDITEUR, PAS EN BASE.** Réécrire
     * la colonne `state` avec un état ancien ne reviendrait en arrière chez
     * PERSONNE : les postes connectés tiennent le document en mémoire, et deux
     * états Yjs se fusionnent au lieu de se remplacer - le texte « retiré »
     * reviendrait au premier enregistrement suivant. Restaurer est donc une
     * MODIFICATION ordinaire, faite dans l'éditeur, qui se propage aux autres et
     * s'annule comme n'importe quelle autre. Le serveur ne fait que rendre le
     * contenu d'alors.
     */
    public function version(Request $request, ProjectTask $task, ProjectTaskDocument $document, ProjectTaskDocumentVersion $version): JsonResponse
    {
        abort_unless($document->isVisibleTo($request->user()), 403);

        return response()->json([
            'html' => TaskDocumentHtml::clean($version->html),
            'revision' => $version->revision,
        ]);
    }

    public function rename(Request $request, ProjectTask $task, ProjectTaskDocument $document): JsonResponse
    {
        abort_unless($document->isEditableBy($request->user()), 403);

        $data = $request->validate(['title' => ['required', 'string', 'max:200']]);
        $document->update(['title' => $data['title'], 'updated_by' => $request->user()?->id]);

        return response()->json(['title' => $document->title]);
    }

    public function destroy(Request $request, ProjectTask $task, ProjectTaskDocument $document): RedirectResponse
    {
        abort_unless($document->isEditableBy($request->user()), 403);

        $document->delete();

        return redirect()
            ->route('admin.project.index', ['event_id' => $task->event_id])
            ->with('ukc_status', 'Document supprimé.');
    }

    private function verserVersion(ProjectTaskDocument $document, ?int $userId, ?string $label = null): void
    {
        $document->versions()->create([
            'revision' => $document->revision,
            'label' => $label,
            'html' => $document->html,
            'state' => $document->state,
            'user_id' => $userId,
        ]);
    }

    /**
     * Historique affiché.
     *
     * ⚠️ Sans `state` ni `html` : la liste part dans la page, et une trentaine
     * d'états complets en feraient plusieurs mégaoctets. Le contenu se demande
     * version par version, au moment où on la consulte.
     *
     * @return list<array<string, mixed>>
     */
    private function versions(ProjectTaskDocument $document): array
    {
        return $document->versions()
            ->with('author')
            ->latest('id')
            ->limit(self::VERSIONS_AFFICHEES)
            ->get()
            ->map(fn (ProjectTaskDocumentVersion $v) => [
                'id' => $v->id,
                'revision' => $v->revision,
                'label' => $v->label,
                'author' => $v->author?->salutationName() ?: $v->author?->name,
                'at' => $v->created_at?->toIso8601String(),
                'atLabel' => $v->created_at?->format('d/m/Y H:i'),
            ])
            ->all();
    }

    /** Créer un document suppose d'être associé à la tâche (ou de l'avoir créée). */
    private function peutRediger(Request $request, ProjectTask $task): bool
    {
        $user = $request->user();

        return $user !== null
            && ($user->isColloqueAdmin()
                || $task->isAssociate($user)
                || (int) $task->created_by === (int) $user->id);
    }
}
