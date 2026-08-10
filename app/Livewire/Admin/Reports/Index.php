<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Edition;
use App\Models\Submission;
use App\Support\EditionReport;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Rapports d'une édition : synthèse chiffrée à l'écran + exports (classeur
 * Excel multi-feuilles et rapport PDF imprimable) délégués au ReportController.
 */
#[Title('Rapports')]
class Index extends Component
{
    public string $edition_id = '';

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    public function render()
    {
        $edition = $this->edition_id !== '' ? Edition::find($this->edition_id) : null;
        $summary = $edition ? (new EditionReport($edition))->summary() : null;

        return view('livewire.admin.reports.index', [
            'editions' => Edition::latest('id')->get(),
            'edition' => $edition,
            'summary' => $summary,
            'statuses' => Submission::statuses(),
        ])->layout('components.layouts.admin', ['title' => 'Rapports']);
    }
}
