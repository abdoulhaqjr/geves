<?php

namespace App\Livewire\Account\Submissions;

use App\Actions\NotifySubmissionStatus;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Fiche d'une communication côté participant : statut, retour des relecteurs,
 * historique des versions, et resoumission d'une version corrigée lorsqu'une
 * correction est demandée.
 */
#[Title('Ma communication')]
class Show extends Component
{
    use WithFileUploads;

    public Submission $submission;

    public string $title = '';
    public string $keywords = '';
    public string $abstract = '';
    public $file = null;

    public function mount(Submission $submission): void
    {
        $account = Auth::guard('account')->user();

        // La communication doit appartenir au compte connecté.
        abort_unless(
            (int) $submission->revision_of === 0
            && $submission->participant
            && (int) $submission->participant->account_id === (int) $account->id,
            404
        );

        $this->submission = $submission;
        $this->title = (string) $submission->title;
        $this->keywords = (string) $submission->keywords;
        $this->abstract = (string) $submission->abstract;
    }

    /** Commentaires des relecteurs destinés à l'auteur (révélés après décision). */
    public function authorFeedback(): array
    {
        if (! in_array($this->submission->status, ['revision', 'accepted', 'rejected'], true)) {
            return [];
        }

        return $this->submission->reviews()->where('status', 'completed')->get()
            ->filter(fn ($r) => filled($r->comments))
            ->values()
            ->map(fn ($r, $i) => ['label' => 'Relecteur ' . ($i + 1), 'comments' => $r->comments])
            ->all();
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'min:50', 'max:8000'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:8192'],
        ];
    }

    public function resubmit(NotifySubmissionStatus $notify)
    {
        abort_unless($this->submission->canBeRevised(), 403);

        $data = $this->validate();

        // Archive la version courante (revision_of = canonique).
        $current = $this->submission;
        Submission::create([
            'edition_id' => $current->edition_id,
            'participant_id' => $current->participant_id,
            'ref_code' => $current->ref_code,
            'access_token' => \Illuminate\Support\Str::random(64),
            'title' => $current->title,
            'authors' => $current->authors,
            'abstract' => $current->abstract,
            'keywords' => $current->keywords,
            'track' => $current->track,
            'type' => $current->type,
            'status' => 'superseded',
            'file_url' => $current->file_url,
            'revision_of' => $current->id,
            'version' => $current->version,
        ]);

        // Nouveau fichier éventuel.
        $fileUrl = $current->file_url;
        if ($this->file) {
            $path = $this->file->store('submissions/' . $current->edition_id, 'public');
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Met à jour la version canonique.
        $current->update([
            'title' => $data['title'],
            'abstract' => $data['abstract'],
            'keywords' => $data['keywords'] ?: null,
            'file_url' => $fileUrl,
            'version' => $current->version + 1,
            'status' => 'submitted',
        ]);

        $notify->handle($current->fresh(), 'submitted');

        $this->file = null;
        session()->flash('ukc_status', 'Version corrigée envoyée. Votre communication est de nouveau en cours d’examen.');

        return $this->redirect(route('account.submissions.show', $current), navigate: true);
    }

    public function render()
    {
        return view('livewire.account.submissions.show', [
            'feedback' => $this->authorFeedback(),
            'versions' => $this->submission->versions()->get(),
        ])->layout('components.layouts.participant', ['title' => 'Ma communication']);
    }
}
