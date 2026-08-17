<?php

namespace App\Livewire\Public;

use App\Livewire\Concerns\TalksToGesco;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Chat public (page dédiée) avec l'assistante Gesco. La logique de conversation
 * est mutualisée dans {@see TalksToGesco} (partagée avec le widget flottant).
 */
#[Title('Assistante Gesco')]
class GescoChat extends Component
{
    use TalksToGesco;

    public function mount(): void
    {
        $this->seedGesco();
    }

    public function render()
    {
        return view('livewire.public.gesco-chat')
            ->layout('components.layouts.app', ['title' => 'Assistante Gesco']);
    }
}
