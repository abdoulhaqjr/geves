<?php

namespace App\Livewire\Admin\Reports;

use App\Actions\GenerateReport;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\EditionReport;
use Illuminate\Support\Carbon;
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

    /** Réinitialise les graphiques quand l'édition change (données remplacées). */
    public function updatedEditionId(): void
    {
        $this->dispatch('charts-refresh');
    }

    public function render()
    {
        $edition = $this->edition_id !== '' ? Edition::find($this->edition_id) : null;
        $report = $edition ? new EditionReport($edition) : null;

        return view('livewire.admin.reports.index', [
            'editions' => Edition::latest('id')->get(),
            'edition' => $edition,
            'summary' => $report?->summary(),
            'statuses' => Submission::statuses(),
            'chartData' => $edition ? $this->chartData($edition->id) : null,
            'report' => $report,
        ])->layout('components.layouts.admin', ['title' => 'Rapports']);
    }

    /**
     * Données des 4 graphiques (mêmes clés/canvas que le tableau de bord :
     * statuts, soumissions/mois, paiements cumulés, axes) pour l'édition donnée.
     *
     * @return array<string, mixed>
     */
    protected function chartData(int $edId): array
    {
        $subs = fn () => Submission::query()->where('revision_of', 0)->where('edition_id', $edId);
        $pay = fn () => Payment::query()->where('edition_id', $edId);

        $byStatus = $subs()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
        $n = fn (string $s): int => (int) ($byStatus[$s] ?? 0);

        $months = collect(range(5, 0))->map(fn ($i) => Carbon::now()->subMonths($i));
        $subsMonth = $months->map(fn (Carbon $m) => $subs()
            ->whereBetween('created_at', [$m->copy()->startOfMonth(), $m->copy()->endOfMonth()])->count());

        $byTrack = $subs()->selectRaw("COALESCE(NULLIF(track, ''), 'Autres') as t, count(*) as c")
            ->groupBy('t')->orderByDesc('c')->limit(6)->pluck('c', 't');

        $paidRows = $pay()->where('status', 'paid')->get(['amount', 'created_at']);
        $weekEnds = collect(range(7, 0))->map(fn ($i) => Carbon::now()->subWeeks($i)->endOfWeek());
        $cumRevenue = $weekEnds->map(fn (Carbon $w) => (int) round($paidRows->where('created_at', '<=', $w)->sum('amount') / 1000));
        $cumCount = $weekEnds->map(fn (Carbon $w) => $paidRows->where('created_at', '<=', $w)->count());

        return [
            'status' => [
                'labels' => ['Soumises', 'En revue', 'Corrections', 'Acceptées', 'Refusées'],
                'data' => [$n('submitted'), $n('under_review'), $n('revision'), $n('accepted'), $n('rejected')],
            ],
            'months' => ['labels' => $months->map(fn (Carbon $m) => ucfirst($m->translatedFormat('M')))->all(), 'data' => $subsMonth->all()],
            'axes' => ['labels' => $byTrack->keys()->all(), 'data' => $byTrack->values()->map(fn ($v) => (int) $v)->all()],
            'payments' => [
                'labels' => collect(range(1, 8))->map(fn ($i) => 'S'.$i)->all(),
                'revenue' => $cumRevenue->all(),
                'count' => $cumCount->all(),
            ],
        ];
    }
}
