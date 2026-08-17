<?php

namespace App\Livewire\Public;

use App\Livewire\Concerns\TalksToGesco;
use Livewire\Component;

/**
 * Widget flottant Gesco (bulle en bas à droite), présent sur les pages
 * publiques et l'espace participant. Réutilise {@see TalksToGesco} ; l'ouverture
 * et l'agrandissement sont gérés côté client (Alpine).
 */
class GescoWidget extends Component
{
    use TalksToGesco;

    public function mount(): void
    {
        $this->seedGesco();
    }

    public function render()
    {
        return view('livewire.public.gesco-widget');
    }
}
