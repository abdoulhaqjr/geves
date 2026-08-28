<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateReport;
use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Submission;
use App\Support\EditionReport;
use App\Support\XlsxWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rapports d'une édition (contrôleur + Blade) : synthèse chiffrée + graphiques
 * à l'écran, exports Excel/PDF, et « Export personnalisé » assisté par IA
 * (brief + exemple optionnel → brouillon, décision humaine). L'édition est
 * choisie via query string ; les graphiques se réinitialisent au rechargement.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $editionId = (string) $request->query('edition_id', '');
        if ($editionId === '') {
            $editionId = (string) (Edition::where('status', 'open')->latest('id')->value('id')
                ?? Edition::latest('id')->value('id') ?? '');
        }

        $edition = $editionId !== '' ? Edition::find($editionId) : null;
        $report = $edition ? new EditionReport($edition) : null;

        // Relectures de l'édition (onglet Relectures — mockup admin-reports).
        $reviewRows = [];
        if ($edition) {
            $reviewRows = Review::whereHas('submission', fn ($q) => $q->where('edition_id', $edition->id))
                ->with(['submission', 'reviewer'])->latest('id')->get()
                ->map(fn ($r) => [
                    trim(($r->submission?->ref_code ? $r->submission->ref_code.' — ' : '').Str::limit((string) $r->submission?->title, 40)),
                    $r->reviewer?->full_name ?: '—',
                    $r->recommendation ?: '—',
                    $r->score !== null ? (string) $r->score : '—',
                    $r->status === 'completed' ? 'Terminé' : ($r->status === 'pending' ? 'En cours' : $r->status),
                ])->all();
        }

        return view('admin.reports.index', [
            'editions' => Edition::latest('id')->get(),
            'edition' => $edition,
            'edition_id' => $editionId,
            'summary' => $report?->summary(),
            'statuses' => Submission::statuses(),
            'chartData' => $edition ? $this->chartData($edition->id) : null,
            'report' => $report,
            'reviewHeaders' => ['Communication', 'Relecteur', 'Recommandation', 'Note', 'Statut'],
            'reviewRows' => $reviewRows,
        ]);
    }

    /** Génère un brouillon de rapport via IA (brief + exemple optionnel). */
    public function generate(Request $request, GenerateReport $action): RedirectResponse
    {
        $request->validate([
            'edition_id' => ['required', 'exists:editions,id'],
            'aiBrief' => ['required', 'string', 'min:5', 'max:2000'],
            'aiExample' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx', 'max:8192'],
        ]);

        $edition = Edition::findOrFail($request->input('edition_id'));

        $files = [];
        if ($request->hasFile('aiExample')) {
            $file = $request->file('aiExample');
            $files[] = ['mime' => (string) $file->getMimeType(), 'data' => base64_encode((string) $file->get())];
        }

        try {
            $report = $action->handle($edition, (string) $request->input('aiBrief'), $files);

            return back()->with('ai_report', ['title' => $report['title'], 'sections' => $report['sections']]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('ai_error', $e->getMessage());
        }
    }

    public function xlsx(Edition $edition): Response
    {
        $report = new EditionReport($edition);
        $summary = $report->summary();

        $synthese = [
            ['Édition', $edition->title ?: (string) $edition->year],
            ['Participants', $summary['participants']],
            ['Communications', $summary['submissions']],
        ];
        foreach (Submission::statuses() as $key => $label) {
            $synthese[] = ['— '.$label, $summary['submissionsByStatus'][$key] ?? 0];
        }
        $synthese[] = ['Paiements réglés', $summary['paidCount']];
        $synthese[] = ['Encaissé ('.$summary['currency'].')', $summary['collected']];
        $synthese[] = ['En attente ('.$summary['currency'].')', $summary['pending']];

        $xlsx = (new XlsxWriter)
            ->sheet('Synthèse', ['Indicateur', 'Valeur'], $synthese)
            ->sheet('Participants', $report->participantHeaders(), $report->participantRows())
            ->sheet('Communications', $report->submissionHeaders(), $report->submissionRows())
            ->sheet('Paiements', $report->paymentHeaders(), $report->paymentRows());

        return $xlsx->download($report->filenameBase().'.xlsx');
    }

    public function pdf(Edition $edition): View
    {
        $report = new EditionReport($edition);

        return view('reports.edition', [
            'edition' => $edition,
            'summary' => $report->summary(),
            'participantHeaders' => $report->participantHeaders(),
            'participantRows' => $report->participantRows(),
            'submissionHeaders' => $report->submissionHeaders(),
            'submissionRows' => $report->submissionRows(),
            'paymentHeaders' => $report->paymentHeaders(),
            'paymentRows' => $report->paymentRows(),
            'statuses' => Submission::statuses(),
        ]);
    }

    /**
     * Données des 4 graphiques (statuts, soumissions/mois, paiements cumulés,
     * axes) pour l'édition donnée — mêmes clés/canvas que le tableau de bord.
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
