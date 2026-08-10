<?php

namespace App\Livewire\Admin\Editions;

use App\Models\Edition;
use App\Models\Media;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Documents & médias d'une édition (table `media`) : liens/ressources exposés
 * aux participants de l'édition dans leur espace (livret, documents, vidéos…).
 */
#[Title('Documents de l’édition')]
class Documents extends Component
{
    public Edition $edition;

    public string $type = 'document';

    public string $title = '';

    public string $url = '';

    public function mount(Edition $edition): void
    {
        $this->edition = $edition;
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', 'in:'.implode(',', array_keys(Media::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
        ];
    }

    public function add(): void
    {
        $data = $this->validate();

        $ordre = (int) Media::where('edition_id', $this->edition->id)->max('ordre') + 1;

        Media::create([
            'edition_id' => $this->edition->id,
            'type' => $data['type'],
            'title' => $data['title'],
            'url' => $data['url'],
            'ordre' => $ordre,
        ]);

        $this->reset('title', 'url');
        $this->type = 'document';

        session()->flash('ukc_status', 'Document ajouté.');
    }

    public function remove(int $id): void
    {
        Media::where('id', $id)->where('edition_id', $this->edition->id)->delete();
        session()->flash('ukc_status', 'Document retiré.');
    }

    public function render()
    {
        return view('livewire.admin.editions.documents', [
            'media' => Media::where('edition_id', $this->edition->id)
                ->orderBy('ordre')->orderBy('id')->get(),
            'types' => Media::TYPES,
        ])->layout('components.layouts.admin', ['title' => 'Documents — '.($this->edition->title ?: $this->edition->year)]);
    }
}
