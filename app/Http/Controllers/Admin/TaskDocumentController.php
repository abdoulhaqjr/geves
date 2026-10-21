<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectTask;
use App\Models\ProjectTaskDocument;
use App\Models\ProjectTaskDocumentVersion;
use App\Support\TaskDocSpace;
use App\Support\TaskDocumentHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Documents de travail d'une tâche de projet, rédigés À PLUSIEURS.
 *
 * ⚠️⚠️ **UN DOCUMENT DE TÂCHE EST UN FICHIER DOCX OU XLSX, ET RIEN D'AUTRE**
 * (21/10/2026). Il vit chez ONLYOFFICE DocSpace, où se tiennent aussi la
 * coédition et l'historique ; GEVES ne conserve que son identifiant, son titre
 * et le jeton de son lien d'édition. Le serveur ne voit donc jamais passer une
 * frappe : il autorise l'accès, et il nomme le fichier.
 *
 * ⚠️ **L'ANCIEN ÉDITEUR TEXTE (Tiptap + Yjs) A ÉTÉ RETIRÉ.** Les documents
 * rédigés avec lui avant cette date ne sont pas perdus pour autant : leur page
 * les rend **en lecture**, contenu courant et versions comprises, parce qu'un
 * texte qu'on cesse d'afficher est un texte perdu. Plus rien ne les écrit.
 */
class TaskDocumentController extends Controller
{
    /** Versions montrées dans le panneau d'historique. */
    private const VERSIONS_AFFICHEES = 40;

    /** Documents au plus par tâche - au-delà, c'est une bibliothèque, pas une tâche. */
    public const MAX_PAR_TACHE = 20;

    public function store(Request $request, ProjectTask $task, TaskDocSpace $docspace): RedirectResponse|JsonResponse
    {
        abort_unless($this->peutRediger($request, $task), 403);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'format' => ['nullable', 'in:docx,xlsx'],
            'file' => ['nullable', 'file', 'mimes:docx,xlsx', 'max:20480'],
        ]);

        abort_if($task->documents()->count() >= self::MAX_PAR_TACHE, 422);

        $file = $request->file('file');
        $format = $file ? strtolower($file->getClientOriginalExtension()) : ($data['format'] ?? null);
        if (! in_array($format, ['docx', 'xlsx'], true)) {
            throw ValidationException::withMessages(['file' => 'Choisissez Word ou Excel, ou importez un fichier DOCX ou XLSX.']);
        }
        if ($file && ! $docspace->validFile($file->getRealPath(), $format)) {
            throw ValidationException::withMessages(['file' => 'Ce fichier Word ou Excel est invalide.']);
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' && $file) {
            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        }
        if ($title === '') {
            throw ValidationException::withMessages(['title' => 'Indiquez le nom du document.']);
        }
        if (str_ends_with(mb_strtolower($title), '.'.$format)) {
            $title = mb_substr($title, 0, -strlen($format) - 1);
        }
        if (trim($title) === '') {
            throw ValidationException::withMessages(['title' => 'Indiquez le nom du document.']);
        }
        $title = mb_substr($title, 0, 200);

        if (! $docspace->configured()) {
            throw ValidationException::withMessages(['file' => 'DocSpace doit être configuré avant de créer ou importer un document de travail.']);
        }
        if (! $docspace->fileLinksEnabled()) {
            throw ValidationException::withMessages(['file' => 'Les liens d’édition DocSpace doivent être activés par l’administrateur.']);
        }

        $docspaceFileId = null;
        try {
            $folderId = $docspace->taskFolder($task);
            $docspaceFileId = $file
                ? $docspace->uploadFile($folderId, $file->getRealPath(), $title.'.'.$format)
                : $docspace->createFile($folderId, $title, $format);
            $docspaceToken = $docspace->createFileEditLink($docspaceFileId);
        } catch (RuntimeException $error) {
            $this->oublierLeFichier($docspace, $docspaceFileId, 'échec du lien d’édition');

            throw ValidationException::withMessages(['file' => $error->getMessage()]);
        }

        try {
            $document = DB::transaction(fn (): ProjectTaskDocument => $task->documents()->create([
                'title' => $title,
                'format' => $format,
                'docspace_file_id' => $docspaceFileId,
                'docspace_request_token' => $docspaceToken,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]));
        } catch (Throwable $error) {
            $this->oublierLeFichier($docspace, $docspaceFileId, 'échec de création GEVES');

            throw $error;
        }

        $url = route('admin.project.documents.edit', [$task, $document]);
        if ($request->expectsJson()) {
            return response()->json(['url' => $url], 201);
        }

        return redirect($url)->with('ukc_status', $file ? 'Document importé.' : 'Document créé.');
    }

    public function edit(Request $request, ProjectTask $task, ProjectTaskDocument $document, TaskDocSpace $docspace): View
    {
        $user = $request->user();
        abort_unless($document->isVisibleTo($user), 403);

        $task->load(['event', 'assignee', 'collaborators']);

        $canWrite = $document->isEditableBy($user);
        $commun = [
            'task' => $task,
            'event' => $task->event,
            'document' => $document,
            'canWrite' => $canWrite,
            'canDelete' => $document->isDeletableBy($user),
            'associates' => $task->collaborators
                ->concat(array_filter([$task->assignee]))
                ->unique('id')
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->salutationName() ?: $u->name])
                ->values()
                ->all(),
        ];

        // ⚠️ Document d'avant le 21/10/2026 : plus aucun éditeur, lecture seule.
        if (! $document->isOfficeFile()) {
            return view('admin.project.document', $commun + [
                'html' => TaskDocumentHtml::clean($document->html),
                'versions' => $this->versionsTexte($document),
            ]);
        }

        $token = null;
        $erreur = null;
        if ($canWrite && $docspace->configured()) {
            try {
                $token = $docspace->fileEditToken($document);
            } catch (RuntimeException $error) {
                $erreur = $error->getMessage();
            }
            $this->revoquerAncienLienDeDossier($docspace, $task, $token);
        }

        return view('admin.project.office-document', $commun + [
            'canRotate' => $user?->isColloqueAdmin() === true,
            'docspaceError' => $erreur,
            'editorReady' => filled($token),
            'officeScript' => filled($token) ? $docspace->scriptUrl() : null,
            'docspaceConfig' => filled($token) ? [
                'frameId' => 'ukcOfficeEditor',
                'src' => $docspace->url(),
                'id' => (string) $document->docspace_file_id,
                'requestToken' => $token,
                'width' => '100%',
                'height' => '100%',
                // ⚠⚠ **UN LIEN DE PARTAGE EST ANONYME, ET L'ÉDITEUR RÉCLAMAIT DONC UN
                // NOM À CHAQUE OUVERTURE** - constaté à l'écran : une boîte « Entrez un
                // nom à utiliser pour la collaboration » barrait le document, alors que
                // GEVES sait parfaitement qui vient d'entrer. On la coupe et on donne
                // le nom nous-mêmes. ⚠️ Ce n'est PAS une identification : le
                // prestataire ne voit toujours qu'un porteur de jeton, et ce nom est
                // déclaratif - c'est GEVES qui tient l'accès, pas lui.
                'editorCustomization' => [
                    'anonymous' => [
                        'request' => false,
                        'label' => mb_substr($user?->salutationName() ?: (string) $user?->name, 0, 64),
                    ],
                ],
            ] : null,
        ]);
    }

    /**
     * Renouveler le lien d'édition d'un document.
     *
     * ⚠️⚠️ **C'EST LA SEULE PARADE À UN JETON DIVULGUÉ.** Elle est aussi
     * déclenchée d'office au retrait d'un collaborateur
     * (`ProjectController::renouvelerLesLiens()`) ; elle reste offerte à la main
     * pour le cas qu'aucune règle ne couvre - un lien recopié dans un courriel,
     * un poste perdu.
     */
    public function rotateLink(Request $request, ProjectTask $task, ProjectTaskDocument $document, TaskDocSpace $docspace): RedirectResponse
    {
        abort_unless($document->isVisibleTo($request->user()) && $request->user()?->isColloqueAdmin(), 403);
        abort_unless($document->isOfficeFile(), 404);
        abort_unless($docspace->configured(), 503, 'DocSpace est indisponible.');

        try {
            $docspace->rotateFileLink($document);
        } catch (RuntimeException $error) {
            return back()->withErrors(['docspace' => $error->getMessage()]);
        }

        return back()->with('ukc_status', 'Le lien d’édition du document a été renouvelé. L’ancien ne vaut plus rien.');
    }

    public function rename(Request $request, ProjectTask $task, ProjectTaskDocument $document, TaskDocSpace $docspace): JsonResponse
    {
        abort_unless($document->isEditableBy($request->user()), 403);

        $data = $request->validate(['title' => ['required', 'string', 'max:200']]);
        $document->update(['title' => $data['title'], 'updated_by' => $request->user()?->id]);

        // ⚠️ Le titre de GEVES fait foi, mais le fichier porte le même nom chez
        // le prestataire : sans cela on chercherait « Note de cadrage » dans une
        // salle qui affiche encore l'ancien titre. Un refus ne bloque rien.
        if ($document->isOfficeFile() && $docspace->configured()) {
            try {
                $docspace->renameFile((string) $document->docspace_file_id, $document->title.'.'.$document->format);
            } catch (Throwable $error) {
                Log::warning('Titre non repris dans DocSpace.', [
                    'document_id' => $document->id, 'reason' => $error->getMessage(),
                ]);
            }
        }

        return response()->json(['title' => $document->title]);
    }

    /**
     * ⚠️ **LE FICHIER DISTANT PART AVANT LA LIGNE, ET C'EST VOULU.** Supprimer
     * la ligne d'abord laisserait chez le prestataire un fichier que plus rien
     * ne référence, avec son lien d'édition toujours vivant - donc lisible et
     * modifiable par qui en a gardé l'adresse. Le prix est qu'on ne supprime pas
     * un document pendant une panne de DocSpace ; l'écran le DIT, au lieu de
     * rendre une page d'erreur.
     */
    public function destroy(Request $request, ProjectTask $task, ProjectTaskDocument $document, TaskDocSpace $docspace): RedirectResponse|JsonResponse
    {
        abort_unless($document->isDeletableBy($request->user()), 403);

        if ($document->isOfficeFile()) {
            try {
                abort_unless($docspace->configured(), 503);
                $docspace->deleteFile((string) $document->docspace_file_id);
            } catch (RuntimeException $error) {
                Log::warning('Document conservé : DocSpace a refusé de retirer le fichier.', [
                    'document_id' => $document->id, 'reason' => $error->getMessage(),
                ]);
                $message = 'DocSpace n’a pas pu retirer le fichier ; le document est donc conservé. Réessayez plus tard.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 503);
                }

                return back()->withErrors(['docspace' => $message]);
            }
        }

        $document->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()
            ->route('admin.project.index', ['event_id' => $task->event_id])
            ->with('ukc_status', 'Document supprimé.');
    }

    public function download(Request $request, ProjectTask $task, ProjectTaskDocument $document, TaskDocSpace $docspace): Response
    {
        abort_unless($document->isVisibleTo($request->user()), 403);
        abort_unless($document->isOfficeFile(), 404);
        abort_unless($docspace->configured(), 503, 'DocSpace est indisponible.');

        return response($docspace->downloadFile((string) $document->docspace_file_id), 200, [
            'Content-Type' => $document->format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$this->downloadName($document).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function downloadName(ProjectTaskDocument $document): string
    {
        $title = preg_replace('/[\x00-\x1F\/\\\\]+/u', '-', $document->title) ?: 'document';

        return $title.'.'.$document->format;
    }

    /** Un fichier créé puis privé de lien resterait orphelin chez le prestataire. */
    private function oublierLeFichier(TaskDocSpace $docspace, ?string $fileId, string $raison): void
    {
        if (! $fileId) {
            return;
        }

        try {
            $docspace->deleteFile($fileId);
        } catch (Throwable) {
            Log::warning('Fichier DocSpace orphelin après '.$raison.'.', ['file_id' => $fileId]);
        }
    }

    /**
     * L'ancien lien portait sur le DOSSIER de la tâche, donc sur tous ses
     * fichiers d'un coup. On ne le coupe qu'une fois le dernier fichier passé à
     * un lien qui lui est propre : l'inverse fermerait la porte avant d'avoir
     * ouvert les autres.
     */
    private function revoquerAncienLienDeDossier(TaskDocSpace $docspace, ProjectTask $task, ?string $token): void
    {
        if (! $token || ! $docspace->fileLinksEnabled() || blank($task->docspace_request_token)) {
            return;
        }
        if ($task->documents()->whereNotNull('docspace_file_id')->whereNull('docspace_request_token')->exists()) {
            return;
        }

        try {
            $docspace->revokeLegacyTaskLink($task);
        } catch (RuntimeException $error) {
            Log::warning('Ancien lien DocSpace du dossier non révoqué.', [
                'task_id' => $task->id, 'reason' => $error->getMessage(),
            ]);
        }
    }

    /**
     * Versions d'un ANCIEN document texte, rendues en lecture.
     *
     * ⚠️ Le contenu est assaini à l'affichage, comme il l'était dans l'éditeur :
     * ce HTML a été écrit par des rédacteurs, et il est relu dans le back-office.
     *
     * @return list<array<string, mixed>>
     */
    private function versionsTexte(ProjectTaskDocument $document): array
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
                'atLabel' => $v->created_at?->format('d/m/Y H:i'),
                'html' => TaskDocumentHtml::clean($v->html),
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
