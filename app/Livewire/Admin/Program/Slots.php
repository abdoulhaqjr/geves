<?php

namespace App\Livewire\Admin\Program;

use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Slot;
use App\Models\Submission;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Créneaux de passage : création des créneaux (session/salle/horaire) et
 * assignation des communications acceptées.
 */
#[Title('Créneaux')]
class Slots extends Component
{
    public string $edition_id = '';

    public string $slSessionId = '';
    public string $slRoom = '';
    public string $slDay = '';
    public string $slStart = '';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    public function addSlot(): void
    {
        $this->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'slSessionId' => ['nullable'],
            'slRoom' => ['nullable', 'string', 'max:150'],
            'slDay' => ['nullable', 'date'],
            'slStart' => ['nullable', 'date_format:H:i'],
        ]);

        $session = $this->slSessionId !== '' ? EventSession::find($this->slSessionId) : null;

        Slot::create([
            'edition_id' => $this->edition_id,
            'session_id' => $session?->id ?? 0,
            'session_label' => $session?->label,
            'room' => $this->slRoom ?: $session?->room,
            'starts_at' => ($this->slDay && $this->slStart) ? $this->slDay . ' ' . $this->slStart . ':00' : null,
            'submission_id' => 0,
            'participant_id' => 0,
        ]);

        $this->reset(['slRoom', 'slDay', 'slStart', 'slSessionId']);
        session()->flash('ukc_status', 'Créneau ajouté.');
    }

    public function deleteSlot(int $id): void
    {
        Slot::whereKey($id)->delete();
        session()->flash('ukc_status', 'Créneau supprimé.');
    }

    public function assign(int $slotId, $submissionId): void
    {
        $slot = Slot::find($slotId);
        if (! $slot) {
            return;
        }

        if ($submissionId === '' || $submissionId === null) {
            $slot->update(['submission_id' => 0, 'participant_id' => 0]);

            return;
        }

        $submission = Submission::find($submissionId);
        if (! $submission || $submission->edition_id !== $slot->edition_id) {
            return;
        }

        // Libère un éventuel autre créneau de cette communication.
        Slot::where('edition_id', $slot->edition_id)
            ->where('submission_id', $submission->id)
            ->whereKeyNot($slot->id)
            ->update(['submission_id' => 0, 'participant_id' => 0]);

        $slot->update([
            'submission_id' => $submission->id,
            'participant_id' => $submission->participant_id ?? 0,
            'chosen_by_participant' => false,
        ]);

        session()->flash('ukc_status', 'Communication assignée.');
    }

    public function render()
    {
        $edition = $this->edition_id !== '' ? Edition::find($this->edition_id) : null;

        $accepted = $edition
            ? $edition->submissions()->where('revision_of', 0)->where('status', 'accepted')->get()
            : collect();

        $slots = $edition
            ? $edition->slots()->with('submission')->orderBy('starts_at')->get()
            : collect();

        return view('livewire.admin.program.slots', [
            'editions' => Edition::latest('id')->get(),
            'sessions' => $edition ? $edition->sessions()->orderBy('ordre')->get() : collect(),
            'creneaux' => $slots, // NB : « slots » est réservé par Livewire 4 (slots nommés).
            'accepted' => $accepted,
        ])->layout('components.layouts.admin', ['title' => 'Créneaux']);
    }
}
