<?php

namespace App\Livewire\Admin\Reports;

use App\Actions\GenerateReport;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\EditionReport;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Rapports d'une édition : synthèse chiffrée à l'écran + exports (classeur
 * Excel multi-feuilles et rapport PDF imprimable) délégués au ReportController.
 * Onglet « Export personnalisé » : brouillon de rapport généré par IA à partir
 * d'un brief (et d'un exemple optionnel) — assistance, la décision reste humaine.
 */
#[Title('Rapports')]
class Index extends Component
{
    use WithFileUploads;

    public string $edition_id = '';

    // Export personnalisé (IA)
    public string $aiBrief = '';

    public $aiExample = null;

    public string $aiTitle = '';

    /** @var list<array{heading: string, body: string}> */
    public array $aiSections = [];

    public ?string $aiError = null;

    public function mount(): void
    {
        $this->edition_id = (string) (Edition::where('status', 'open')->latest('id')->value('id')
            ?? Edition::latest('id')->value('id') ?? '');
    }

    public function generateReport(GenerateReport $action): void
    {
        $this->validate([
            'aiBrief' => ['required', 'string', 'min:5', 'max:2000'],
            'aiExample' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx', 'max:8192'],
        ]);

        $edition = $this->edition_id !== '' ? Edition::find($this->edition_id) : null;
        if (! $edition) {
            $this->aiError = 'Sélectionnez d’abord une édition.';

            return;
        }

        $files = [];
        if ($this->aiExample) {
            $files[] = ['mime' => (string) $this->aiExample->getMimeType(), 'data' => base64_encode((string) $this->aiExample->get())];
        }

        try {
            $report = $action->handle($edition, $this->aiBrief, $files);
            $this->aiTitle = $report['title'];
            $this->aiSections = $report['sections'];
            $this->aiError = null;
        } catch (\Throwable $e) {
            $this->aiError = $e->getMessage();
            $this->aiSections = [];
        }
    }

    public function resetReport(): void
    {
        $this->reset('aiTitle', 'aiSections', 'aiError', 'aiExample');
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
