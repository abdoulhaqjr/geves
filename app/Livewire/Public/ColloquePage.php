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

        return view('livewire.public.colloque-page', [
            'intro' => $settings['intro'] ?? null,
            'tracks' => $settings['tracks'] ?? [],
            'fees' => $settings['fees'] ?? [],
        ])->title($this->edition->title);
    }
}
