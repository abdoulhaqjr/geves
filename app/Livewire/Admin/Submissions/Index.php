<?php

namespace App\Livewire\Admin\Submissions;

use App\Models\Edition;
use App\Models\Submission;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste des soumissions du back-office : filtres par édition, statut et
 * recherche (référence, titre, auteur).
 */
#[Title('Soumissions')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $edition_id = '';

    public string $type = '';

    public string $track = '';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status', 'edition_id', 'type', 'track'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $submissions = Submission::query()
            ->with(['edition', 'participant'])
            ->where('revision_of', 0)
            ->when($this->edition_id !== '', fn ($q) => $q->where('edition_id', $this->edition_id))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when($this->track !== '', fn ($q) => $q->where('track', $this->track))
            ->when($this->search !== '', function ($q) {
                $term = "%{$this->search}%";
                $q->where(fn ($sub) => $sub
                    ->where('title', 'like', $term)
                    ->orWhere('ref_code', 'like', $term)
                    ->orWhereHas('participant', fn ($p) => $p
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)));
            })
            ->latest('id')
            ->paginate(15);

        // Indicateurs par statut (sur l'édition filtrée), indépendants des autres filtres.
        $counts = Submission::query()->where('revision_of', 0)
            ->when($this->edition_id !== '', fn ($q) => $q->where('edition_id', $this->edition_id))
            ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $c = fn (string $s): int => (int) ($counts[$s] ?? 0);

        return view('livewire.admin.submissions.index', [
            'submissions' => $submissions,
            'editions' => Edition::latest('id')->get(),
            'statuses' => Submission::statuses(),
            'statusPills' => Submission::statusPills(),
            'tracks' => Submission::query()->where('revision_of', 0)
                ->when($this->edition_id !== '', fn ($q) => $q->where('edition_id', $this->edition_id))
                ->whereNotNull('track')->where('track', '!=', '')->distinct()->orderBy('track')->pluck('track'),
            'stats' => [
                'total' => (int) $counts->sum(),
                'underReview' => $c('under_review') + $c('submitted'),
                'accepted' => $c('accepted'),
                'revision' => $c('revision'),
                'rejected' => $c('rejected'),
            ],
        ])->layout('components.layouts.admin', ['title' => 'Soumissions']);
    }
}
