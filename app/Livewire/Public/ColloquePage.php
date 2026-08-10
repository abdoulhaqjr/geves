<?php

namespace App\Livewire\Public;

use App\Models\Edition;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Page publique d'une édition de colloque (accessible seulement si publiée).
 */
#[Layout('components.layouts.app')]
class ColloquePage extends Component
{
    public Edition $edition;

    public function mount(string $slug): void
    {
        $edition = Edition::with('colloque')->where('slug', $slug)->first();

        abort_unless($edition && $edition->isPublished(), 404);

        $this->edition = $edition;
    }

    public function render()
    {
        $settings = $this->edition->settings ?? [];

        // Communications programmées, groupées par session (via les créneaux).
        $commsBySession = $this->edition->slots()
            ->where('submission_id', '>', 0)
            ->with('submission')
            ->orderBy('starts_at')
            ->get()
            ->groupBy('session_id');

        return view('livewire.public.colloque-page', [
            'intro' => $settings['intro'] ?? null,
            'tracks' => $settings['tracks'] ?? [],
            'fees' => $settings['fees'] ?? [],
            'partners' => $settings['partners'] ?? [],
            'faq' => $settings['faq'] ?? [],
            'plenaries' => $this->edition->sessions()->where('type', 'plenary')->orderBy('ordre')->orderBy('starts_at')->get(),
            'ateliers' => $this->edition->ateliers()->with(['sessions' => fn ($q) => $q->orderBy('ordre')])->orderBy('ordre')->get(),
            'commsBySession' => $commsBySession,
            'committees' => $this->edition->committeeMembers()->orderBy('ordre')->get()->groupBy('role'),
        ])->title($this->edition->title);
    }
}
