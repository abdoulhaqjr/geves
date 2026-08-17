<?php

namespace App\Livewire\Account\Submissions;

use App\Actions\NotifySubmissionStatus;
use App\Models\Slot;
use App\Models\Submission;
use App\Support\Html;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            ->map(fn ($r, $i) => ['label' => 'Relecteur '.($i + 1), 'comments' => $r->comments])
            ->all();
    }

    /** Créneau actuellement assigné à cette communication. */
    public function currentSlot(): ?Slot
    {
        return Slot::where('submission_id', $this->submission->id)->first();
    }

    /** Créneaux libres proposés au participant (communication acceptée). */
    public function availableSlots()
    {
        if ($this->submission->status !== 'accepted') {
            return collect();
        }

        return Slot::where('edition_id', $this->submission->edition_id)
            ->where('submission_id', 0)
            ->orderBy('starts_at')
            ->get();
    }

    /** Le participant choisit lui-même un créneau libre. */
    public function chooseSlot(int $slotId): void
    {
        abort_unless($this->submission->status === 'accepted', 403);

        $slot = Slot::where('id', $slotId)
            ->where('edition_id', $this->submission->edition_id)
            ->where('submission_id', 0)
            ->first();

        if (! $slot) {
            session()->flash('ukc_error', 'Ce créneau n’est plus disponible.');

            return;
        }

        // Libère l'ancien créneau puis prend le nouveau.
        Slot::where('submission_id', $this->submission->id)
            ->update(['submission_id' => 0, 'participant_id' => 0, 'chosen_by_participant' => false]);

        $slot->update([
            'submission_id' => $this->submission->id,
            'participant_id' => $this->submission->participant_id ?? 0,
            'chosen_by_participant' => true,
        ]);

        session()->flash('ukc_status', 'Votre créneau de passage est confirmé.');
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'abstract' => ['required', 'string', 'max:20000', function (string $attr, string $value, \Closure $fail): void {
                $len = Html::textLength($value);
                if ($len < 50) {
                    $fail('Le résumé doit contenir au moins 50 caractères.');
                } elseif ($len > 8000) {
                    $fail('Le résumé ne doit pas dépasser 8000 caractères.');
                }
            }],
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
            'access_token' => Str::random(64),
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
            $path = $this->file->store('submissions/'.$current->edition_id, 'public');
            $fileUrl = Storage::disk('public')->url($path);
        }

        // Met à jour la version canonique.
        $current->update([
            'title' => $data['title'],
            'abstract' => Html::clean($data['abstract']),
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
