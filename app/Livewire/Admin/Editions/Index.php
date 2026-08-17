<?php

namespace App\Livewire\Admin\Editions;

use App\Models\Edition;
use App\Models\Payment;
use App\Models\Submission;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des éditions du back-office (recherche, statut, actions).
 */
#[Title('Éditions')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function delete(int $id): void
    {
        Edition::whereKey($id)->delete();
        session()->flash('ukc_status', 'Édition supprimée.');
    }

    /** Définit une édition comme active (publiée/ouverte sur le site public). */
    public function activate(int $id): void
    {
        $edition = Edition::find($id);
        if (! $edition) {
            return;
        }

        $edition->update(['status' => 'open']);
        session()->flash('ukc_status', 'Édition « '.$edition->title.' » activée.');
    }

    public function render()
    {
        $editions = Edition::query()
            ->with('colloque')
            ->withCount([
                'submissions as submissions_count' => fn ($q) => $q->where('revision_of', 0),
                'participants as participants_count',
            ])
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest('id')
            ->paginate(10);

        $active = Edition::where('status', 'open')->latest('id')->first();
        $paid = $active
            ? Payment::where('edition_id', $active->id)->where('status', 'paid')->distinct('participant_id')->count('participant_id')
            : 0;

        return view('livewire.admin.editions.index', [
            'editions' => $editions,
            'stats' => [
                'total' => Edition::count(),
                'activeYear' => $active?->year,
                'activeSubmissions' => $active ? Submission::where('edition_id', $active->id)->where('revision_of', 0)->count() : 0,
                'paidParticipants' => $paid,
            ],
        ])->layout('components.layouts.admin', ['title' => 'Éditions']);
    }
}
