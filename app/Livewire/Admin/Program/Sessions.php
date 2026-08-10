<?php

namespace App\Livewire\Admin\Program;

use App\Models\Atelier;
use App\Models\Edition;
use App\Models\EventSession;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Gestion du programme d'une édition : ateliers (regroupant des axes) et
 * sessions (plénières ou rattachées à un atelier, avec modérateur/rapporteur).
 */
#[Title('Programme — ateliers & sessions')]
class Sessions extends Component
{
    public string $edition_id = '';

    // Formulaire atelier
    public string $atelierTitle = '';

    public array $atelierTracks = [];

    // Formulaire session
    public string $sType = 'plenary';

    public string $sAtelierId = '';

    public string $sLabel = '';

    public string $sDay = '';

    public string $sStart = '';

    public string $sEnd = '';

    public string $sRoom = '';

    public string $sModerator = '';

    public string $sRapporteur = '';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    protected function edition(): ?Edition
    {
        return $this->edition_id !== '' ? Edition::find($this->edition_id) : null;
    }

    public function tracks(): array
    {
        return $this->edition()?->settings['tracks'] ?? [];
    }

    public function addAtelier(): void
    {
        $this->validate(['atelierTitle' => ['required', 'string', 'max:255'], 'edition_id' => ['required', 'exists:editions,id']]);

        Atelier::create([
            'edition_id' => $this->edition_id,
            'title' => $this->atelierTitle,
            'tracks' => array_values($this->atelierTracks),
            'ordre' => Atelier::where('edition_id', $this->edition_id)->count(),
        ]);

        $this->reset(['atelierTitle', 'atelierTracks']);
        session()->flash('ukc_status', 'Atelier ajouté.');
    }

    public function deleteAtelier(int $id): void
    {
        Atelier::whereKey($id)->delete();
        EventSession::where('atelier_id', $id)->update(['atelier_id' => 0, 'type' => 'plenary']);
        session()->flash('ukc_status', 'Atelier supprimé.');
    }

    public function addSession(): void
    {
        $this->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'sType' => ['required', 'in:plenary,atelier'],
            'sAtelierId' => ['nullable'],
            'sLabel' => ['required', 'string', 'max:255'],
            'sDay' => ['nullable', 'date'],
            'sStart' => ['nullable', 'date_format:H:i'],
            'sEnd' => ['nullable', 'date_format:H:i'],
            'sRoom' => ['nullable', 'string', 'max:150'],
            'sModerator' => ['nullable', 'string', 'max:200'],
            'sRapporteur' => ['nullable', 'string', 'max:200'],
        ]);

        EventSession::create([
            'edition_id' => $this->edition_id,
            'atelier_id' => $this->sType === 'atelier' && $this->sAtelierId !== '' ? (int) $this->sAtelierId : 0,
            'type' => $this->sType,
            'label' => $this->sLabel,
            'session_day' => $this->sDay ?: null,
            'starts_at' => $this->composeDateTime($this->sDay, $this->sStart),
            'ends_at' => $this->composeDateTime($this->sDay, $this->sEnd),
            'room' => $this->sRoom ?: null,
            'moderator_name' => $this->sModerator ?: null,
            'rapporteur_name' => $this->sRapporteur ?: null,
            'ordre' => EventSession::where('edition_id', $this->edition_id)->count(),
        ]);

        $this->reset(['sLabel', 'sDay', 'sStart', 'sEnd', 'sRoom', 'sModerator', 'sRapporteur', 'sAtelierId']);
        session()->flash('ukc_status', 'Session ajoutée.');
    }

    public function deleteSession(int $id): void
    {
        EventSession::whereKey($id)->delete();
        session()->flash('ukc_status', 'Session supprimée.');
    }

    protected function composeDateTime(?string $day, ?string $time): ?string
    {
        if (! $day || ! $time) {
            return null;
        }

        return $day.' '.$time.':00';
    }

    public function render()
    {
        $edition = $this->edition();

        return view('livewire.admin.program.sessions', [
            'editions' => Edition::latest('id')->get(),
            'ateliers' => $edition ? $edition->ateliers()->with('sessions')->orderBy('ordre')->get() : collect(),
            'plenaries' => $edition ? $edition->sessions()->where('type', 'plenary')->orderBy('ordre')->get() : collect(),
        ])->layout('components.layouts.admin', ['title' => 'Programme']);
    }
}
