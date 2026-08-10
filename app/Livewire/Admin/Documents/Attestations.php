<?php

namespace App\Livewire\Admin\Documents;

use App\Actions\IssueAttestation;
use App\Models\Attestation;
use App\Models\Edition;
use App\Models\Participant;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Émission des attestations vérifiables d'une édition (par type et audience),
 * et gestion (révocation) des attestations émises.
 */
#[Title('Attestations')]
class Attestations extends Component
{
    public string $edition_id = '';
    public string $type = 'participation';
    public string $audience = 'paid';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    /** Participants ciblés selon l'audience choisie. */
    protected function targets(): \Illuminate\Support\Collection
    {
        $query = Participant::where('edition_id', $this->edition_id);

        return match ($this->audience) {
            'paid' => $query->whereHas('payments', fn ($q) => $q->where('status', 'paid'))->get(),
            'communicants' => $query->whereHas('submissions', fn ($q) => $q->where('revision_of', 0)->where('status', 'accepted'))->get(),
            default => $query->get(),
        };
    }

    public function issue(IssueAttestation $action): void
    {
        if ($this->edition_id === '') {
            return;
        }

        $count = 0;
        foreach ($this->targets() as $participant) {
            $action->handle($participant, $this->type);
            $count++;
        }

        session()->flash('ukc_status', $count . ' attestation(s) « ' . (Attestation::TYPES[$this->type] ?? $this->type) . ' » émise(s).');
    }

    public function revoke(int $id): void
    {
        Attestation::whereKey($id)->update(['revoked' => true]);
        session()->flash('ukc_status', 'Attestation révoquée.');
    }

    public function restore(int $id): void
    {
        Attestation::whereKey($id)->update(['revoked' => false]);
        session()->flash('ukc_status', 'Attestation rétablie.');
    }

    public function render()
    {
        $attestations = $this->edition_id !== ''
            ? Attestation::where('edition_id', $this->edition_id)->latest('id')->get()
            : collect();

        return view('livewire.admin.documents.attestations', [
            'editions' => Edition::latest('id')->get(),
            'types' => Attestation::TYPES,
            'attestations' => $attestations,
        ])->layout('components.layouts.admin', ['title' => 'Attestations']);
    }
}
