<?php

namespace App\Livewire\Admin\Editions;

use App\Models\Edition;
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

    public function render()
    {
        $editions = Edition::query()
            ->with('colloque')
            ->when($this->search !== '', fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest('id')
            ->paginate(10);

        return view('livewire.admin.editions.index', [
            'editions' => $editions,
        ])->layout('components.layouts.admin', ['title' => 'Éditions']);
    }
}
