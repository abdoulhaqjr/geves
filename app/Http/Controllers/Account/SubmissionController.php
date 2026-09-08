<?php

namespace App\Http\Controllers\Account;

use App\Actions\CreateSubmission;
use App\Actions\NotifySubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ResubmitSubmissionRequest;
use App\Http\Requests\Account\StoreSubmissionRequest;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\CalendarExport;
use App\Support\CurrentColloque;
use App\Support\Html;
use App\Support\SubmissionFormConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Communications du compte connecté (contrôleur + Blade, sans Livewire) :
 * liste, dépôt (wizard multi-étapes piloté par Alpine + POST multipart), fiche
 * de suivi (statut, retours relecteurs, versions), choix de créneau et
 * resoumission d'une version corrigée.
 */
class SubmissionController extends Controller
{
    public function index(): View
    {
        $account = Auth::guard('account')->user();

        // Colloque courant (sélecteur) : la liste se scope dessus.
        $current = CurrentColloque::current($account);

        $submissions = Submission::query()
            ->canonical()
            ->with('edition')
            ->whereHas('participant', fn ($q) => $q->where('account_id', $account->id))
            ->when($current, fn ($q) => $q->where('edition_id', $current->id))
            ->latest('id')
            ->get();

        // Édition ouverte aux soumissions (pour « Nouvelle soumission ») + échéance.
        $openEdition = Edition::where('status', 'open')->where('submission_open', true)
            ->latest('id')->first();
        $deadlineEdition = $openEdition
            ?? $submissions->first()?->edition
            ?? Edition::latest('id')->first();

        return view('account.submissions.index', [
            'submissions' => $submissions,
            'openEdition' => $openEdition,
            'deadline' => $deadlineEdition?->submission_deadline,
            'callOpen' => (bool) $openEdition,
        ]);
    }

    /** Formulaire de dépôt d'une communication (wizard). */
    public function create(string $slug): View
    {
        $edition = Edition::where('slug', $slug)->first();
        abort_unless($edition && $edition->isPublished(), 404);

        $account = Auth::guard('account')->user();
        $config = new SubmissionFormConfig($edition);

        return view('account.submissions.create', [
            'edition' => $edition,
            'config' => $config,
            'account' => $account,
            'tracks' => $edition->settings['tracks'] ?? [],
            'maxCommunications' => (int) ($edition->settings['max_communications'] ?? 3),
            'remaining' => Submission::remainingFor($edition, $account),
            'successRef' => session('success_ref'),
        ]);
    }

    /** Enregistre la communication déposée (validation : StoreSubmissionRequest). */
    public function store(StoreSubmissionRequest $request, CreateSubmission $action): RedirectResponse
    {
        $edition = $request->edition();
        $account = Auth::guard('account')->user();

        $submission = $action->handle($edition, $account, $request->config(), $request->validated(), $request->file('file'));

        return redirect()->route('account.submissions.create', $edition->slug)
            ->with('success_ref', $submission->ref_code);
    }

    public function show(Submission $submission): View
    {
        $this->authorizeOwnership($submission);

        $soumissionAtt = $submission->participant_id
            ? Attestation::where('participant_id', $submission->participant_id)
                ->where('edition_id', $submission->edition_id)
                ->where('type', 'soumission')->where('revoked', false)->first()
            : null;

        $slot = $this->currentSlot($submission);

        return view('account.submissions.show', [
            'submission' => $submission,
            'feedback' => $this->authorFeedback($submission),
            'versions' => $submission->versions()->get(),
            'slot' => $slot,
            'availableSlots' => $this->availableSlots($submission),
            'soumissionAtt' => $soumissionAtt,
            'abstractMax' => (new SubmissionFormConfig($submission->edition))->abstractMax(),
            'googleCal' => $slot && $slot->starts_at
                ? app(CalendarExport::class)->googleUrl(AgendaController::slotEvent($slot))
                : null,
        ]);
    }

    /** Le participant choisit lui-même un créneau libre. */
    public function chooseSlot(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->status === 'accepted', 403);

        $slot = Slot::where('id', (int) $request->input('slot_id'))
            ->where('edition_id', $submission->edition_id)
            ->where('submission_id', 0)
            ->first();

        if (! $slot) {
            return back()->with('ukc_error', 'Ce créneau n’est plus disponible.');
        }

        // Libère l'ancien créneau puis prend le nouveau.
        Slot::where('submission_id', $submission->id)
            ->update(['submission_id' => 0, 'participant_id' => 0, 'chosen_by_participant' => false]);

        $slot->update([
            'submission_id' => $submission->id,
            'participant_id' => $submission->participant_id ?? 0,
            'chosen_by_participant' => true,
        ]);

        return back()->with('ukc_status', 'Votre créneau de passage est confirmé.');
    }

    /** Dépôt de la version définitive (camera-ready) après acceptation. */
    public function cameraReady(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->status === 'accepted', 403);

        $rules = ['file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:8192']];
        foreach (array_keys(Submission::CAMERA_READY_CHECKLIST) as $key) {
            $rules['checklist.'.$key] = ['accepted'];
        }
        $request->validate($rules, [
            'checklist.*.accepted' => 'Merci de confirmer tous les points de la liste avant de déposer.',
            'file.required' => 'Joignez le fichier de la version définitive.',
        ]);

        $path = $request->file('file')->store('submissions/'.$submission->edition_id.'/camera-ready', 'public');

        $meta = $submission->meta ?? [];
        $meta['camera_ready'] = [
            'file_url' => Storage::disk('public')->url($path),
            'submitted_at' => now()->toIso8601String(),
            'checklist' => array_keys(Submission::CAMERA_READY_CHECKLIST),
        ];
        $submission->update(['meta' => $meta]);

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Version définitive déposée. Merci !');
    }

    public function resubmit(ResubmitSubmissionRequest $request, Submission $submission, NotifySubmissionStatus $notify): RedirectResponse
    {
        $this->authorizeOwnership($submission);
        abort_unless($submission->canBeRevised(), 403);

        $data = $request->validated();

        // Archive la version courante (revision_of = canonique).
        Submission::create([
            'edition_id' => $submission->edition_id,
            'participant_id' => $submission->participant_id,
            'ref_code' => $submission->ref_code,
            'access_token' => Str::random(64),
            'title' => $submission->title,
            'authors' => $submission->authors,
            'abstract' => $submission->abstract,
            'keywords' => $submission->keywords,
            'track' => $submission->track,
            'type' => $submission->type,
            'status' => 'superseded',
            'file_url' => $submission->file_url,
            'revision_of' => $submission->id,
            'version' => $submission->version,
        ]);

        // Nouveau fichier éventuel.
        $fileUrl = $submission->file_url;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('submissions/'.$submission->edition_id, 'public');
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Met à jour la version canonique.
        $submission->update([
            'title' => $data['title'],
            'abstract' => Html::clean($data['abstract']),
            'keywords' => ($data['keywords'] ?? null) ?: null,
            'file_url' => $fileUrl,
            'version' => $submission->version + 1,
            'status' => 'submitted',
        ]);

        $notify->handle($submission->fresh(), 'submitted');

        return redirect()->route('account.submissions.show', $submission)
            ->with('ukc_status', 'Version corrigée envoyée. Votre communication est de nouveau en cours d’examen.');
    }

    // --- Helpers (suivi de communication) -------------------------------------

    /** La communication doit appartenir au compte connecté (version canonique). */
    private function authorizeOwnership(Submission $submission): void
    {
        $account = Auth::guard('account')->user();

        abort_unless(
            (int) $submission->revision_of === 0
            && $submission->participant
            && (int) $submission->participant->account_id === (int) $account->id,
            404
        );
    }

    /** Commentaires des relecteurs destinés à l'auteur (révélés après décision). */
    private function authorFeedback(Submission $submission): array
    {
        if (! in_array($submission->status, ['revision', 'accepted', 'rejected'], true)) {
            return [];
        }

        return $submission->reviews()->where('status', 'completed')->get()
            ->filter(fn ($r) => filled($r->comments))
            ->values()
            ->map(fn ($r, $i) => ['label' => 'Relecteur '.($i + 1), 'comments' => $r->comments])
            ->all();
    }

    /** Créneau actuellement assigné à cette communication. */
    private function currentSlot(Submission $submission): ?Slot
    {
        return Slot::where('submission_id', $submission->id)->first();
    }

    /** Créneaux libres proposés au participant (communication acceptée). */
    private function availableSlots(Submission $submission)
    {
        if ($submission->status !== 'accepted') {
            return collect();
        }

        return Slot::where('edition_id', $submission->edition_id)
            ->where('submission_id', 0)
            ->orderBy('starts_at')
            ->get();
    }
}
