<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GenerateReport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Submission;
use App\Support\EventReport;
use App\Support\Settings;
use App\Support\XlsxWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapports d'un événement (contrôleur + Blade) : synthèse chiffrée + graphiques
 * à l'écran, exports Excel/PDF, et « Export personnalisé » assisté par IA
 * (brief + exemple optionnel → brouillon, décision humaine). L'événement est
 * choisie via query string ; les graphiques se réinitialisent au rechargement.
 */
class ReportController extends Controller
{
    use Concerns\ScopesEditionAccess;

    public function index(Request $request): View
    {
        $events = $this->accessibleEditions($request, 'view_dashboard');
        $editionId = (string) $request->query('event_id', '');
        if ($editionId === '') {
            $editionId = (string) ($events->firstWhere('status', 'open')?->id ?? $events->first()?->id ?? '');
        }

        $event = $editionId !== '' ? $events->firstWhere('id', (int) $editionId) : null;
        $report = $event ? new EventReport($event) : null;

        // Historique des exports IA (persisté), filtré sur l'événement courant.
        $aiHistory = collect((array) Settings::get('report_ai_history', []))
            ->filter(fn ($h) => (string) ($h['event_id'] ?? '') === $editionId)
            ->take(12)
            ->values()
            ->all();

        return view('admin.reports.index', [
            'events' => $events,
            'event' => $event,
            'event_id' => $editionId,
            'summary' => $report?->summary(),
            'statuses' => Submission::statuses(),
            'chartData' => $event ? $this->chartData($event->id) : null,
            'report' => $report,
            'counts' => $report?->counts(),
            'aiHistory' => $aiHistory,
        ]);
    }

    /**
     * Export d'un jeu de données de l'événement en CSV ou Excel (onglet
     * « Exports prêts »). Réutilise les lignes d'EventReport.
     */
    public function export(Event $event, string $dataset, string $format): Response
    {
        abort_unless(in_array($dataset, ['submissions', 'participants', 'payments', 'attestations', 'accounting', 'session_reports'], true), 404);
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $report = new EventReport($event);
        [$sheet, $headers, $rows] = match ($dataset) {
            'submissions' => ['Communications', $report->submissionHeaders(), $report->submissionRows()],
            'participants' => ['Participants', $report->participantHeaders(), $report->participantRows()],
            'payments' => ['Paiements', $report->paymentHeaders(), $report->paymentRows()],
            'attestations' => ['Attestations', $report->attestationHeaders(), $report->attestationRows()],
            'accounting' => ['Comptabilité', $report->accountingHeaders(), $report->accountingRows()],
            'session_reports' => ['Rapports de séance', $report->sessionReportHeaders(), $report->sessionReportRows()],
        };

        $filename = $report->filenameBase().'-'.$dataset;

        if ($format === 'xlsx') {
            return (new XlsxWriter)->sheet($sheet, $headers, $rows)->download($filename.'.xlsx');
        }

        return $this->csv($filename.'.csv', $headers, $rows);
    }

    /**
     * Télécharge un rapport IA de l'historique dans son format d'origine.
     * Le brouillon (narratif) est stocké ; les données réelles sont réagrégées.
     */
    public function aiDownload(Event $event, string $id): Response
    {
        $entry = collect((array) Settings::get('report_ai_history', []))
            ->firstWhere('id', $id);
        abort_if($entry === null, 404);

        $format = (string) ($entry['format'] ?? 'pdf');
        $scope = (string) ($entry['scope'] ?? 'event');
        $report = new EventReport($event);
        $base = 'rapport-ia-'.Str::slug((string) ($entry['title'] ?? 'rapport')).'-'.$id;

        $datasets = $this->datasets($report, $scope);
        $table = is_array($entry['table'] ?? null) ? $entry['table'] : null;

        if ($format === 'xlsx') {
            $writer = new XlsxWriter;

            // ⚠️ Le tableau produit par l'IA EST le rapport demandé : il ouvre le
            // classeur. Les jeux de données bruts ne suivent qu'à défaut - les
            // joindre systématiquement noyait la réponse sous toute la base.
            if ($table !== null) {
                $writer->sheet('Rapport', $table['columns'], $table['rows']);
            } else {
                $narrative = [];
                foreach ((array) ($entry['sections'] ?? []) as $s) {
                    $narrative[] = [(string) ($s['heading'] ?? ''), (string) ($s['body'] ?? '')];
                }
                $writer->sheet('Rapport', ['Section', 'Contenu'], $narrative ?: [['Rapport', (string) ($entry['title'] ?? '')]]);
                foreach ($datasets as [$name, $headers, $rows]) {
                    $writer->sheet($name, $headers, $rows);
                }
            }

            return $writer->download($base.'.xlsx');
        }

        if ($format === 'csv') {
            // Un CSV n'a qu'une table : celle demandée, sinon le périmètre brut.
            [, $headers, $rows] = $datasets[0];

            return $table !== null
                ? $this->csv($base.'.csv', $table['columns'], $table['rows'])
                : $this->csv($base.'.csv', $headers, $rows);
        }

        // pdf / docx → vue imprimable (le docx s'ouvre dans Word via l'en-tête).
        $view = view('reports.ai', [
            'event' => $event,
            'entry' => $entry,
            'table' => $table,
            // Les données brutes ne sont jointes QUE si l'IA n'a pas produit le
            // tableau demandé : sinon elles doublent la réponse.
            'datasets' => $table === null ? $datasets : [],
        ]);

        if ($format === 'docx') {
            return response($view->render(), 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$base.'.doc"',
            ]);
        }

        // PDF : page imprimable (Imprimer → Enregistrer en PDF).
        return response($view->render());
    }

    /**
     * Jeux de données du périmètre demandé : ce que l'IA a le droit de lire, et
     * ce qu'on joint au fichier à défaut de tableau.
     *
     * @return array<int, array{0: string, 1: list<string>, 2: array<int, array<int, string|float|int>>}>
     */
    protected function datasets(EventReport $report, string $scope): array
    {
        return match ($scope) {
            'subs' => [['Communications', $report->submissionHeaders(), $report->submissionRows()]],
            'part' => [['Participants', $report->participantHeaders(), $report->participantRows()]],
            'pay' => [['Paiements', $report->paymentHeaders(), $report->paymentRows()]],
            'reports' => [['Rapports de séance', $report->sessionReportHeaders(), $report->sessionReportRows()]],
            default => [
                ['Participants', $report->participantHeaders(), $report->participantRows()],
                ['Communications', $report->submissionHeaders(), $report->submissionRows()],
                ['Paiements', $report->paymentHeaders(), $report->paymentRows()],
                ['Rapports de séance', $report->sessionReportHeaders(), $report->sessionReportRows()],
            ],
        };
    }

    /** Écrit un CSV en flux (UTF-8 BOM pour Excel). */
    protected function csv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Génère un brouillon de rapport via IA (description ou modèle joint). */
    public function generate(Request $request, GenerateReport $action): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'mode' => ['nullable', 'in:describe,template'],
            'aiBrief' => ['nullable', 'string', 'max:2000'],
            'aiNotes' => ['nullable', 'string', 'max:2000'],
            'aiExample' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx,xls,xlsx,csv', 'max:10240'],
            'format' => ['nullable', 'in:xlsx,csv,pdf,docx'],
            'scope' => ['nullable', 'in:event,subs,part,pay,reports,all'],
            'aiProvider' => ['nullable', 'in:default,claude,openai,gemini,grok'],
        ]);

        $mode = $data['mode'] ?? 'describe';
        $format = $data['format'] ?? 'xlsx';
        $scope = $data['scope'] ?? 'event';

        // En mode « description » le brief est requis ; en mode « modèle » le fichier l'est.
        if ($mode === 'describe' && Str::length(trim((string) ($data['aiBrief'] ?? ''))) < 5) {
            return back()->withInput()->withErrors(['aiBrief' => 'Décrivez le rapport souhaité (au moins quelques mots).']);
        }
        if ($mode === 'template' && ! $request->hasFile('aiExample')) {
            return back()->withInput()->withErrors(['aiExample' => 'Joignez un modèle de rapport (PDF, Word, Excel).']);
        }

        $event = Event::findOrFail($data['event_id']);

        $files = [];
        if ($request->hasFile('aiExample')) {
            $file = $request->file('aiExample');
            $files[] = ['mime' => (string) $file->getMimeType(), 'data' => base64_encode((string) $file->get())];
        }

        $brief = trim((string) ($data['aiBrief'] ?? ''));
        if ($mode === 'template') {
            $brief = trim('Reproduis la structure du modèle joint. '.(string) ($data['aiNotes'] ?? ''));
        }

        // Le FORMAT décide de la forme : un Excel ou un CSV veut un tableau,
        // un PDF ou un Word un texte rédigé. C'est ce qui manquait : l'IA
        // rendait de la prose quel que soit le fichier demandé.
        $output = in_array($format, ['xlsx', 'csv'], true) ? 'table' : 'document';

        // ⚠️ Sortir des 30 s par défaut de PHP : depuis que l'IA reçoit les
        // lignes réelles, une liste de cent enregistrements se rédige en une à
        // deux minutes. Sans cela la requête mourait sans message, et l'écran
        // restait sur son voile de travail.
        @set_time_limit(300);

        try {
            $report = $action->handle(
                $event,
                $brief,
                $files,
                $data['aiProvider'] ?? null,
                'report',
                $this->datasets(new EventReport($event), $scope),
                $output,
            );

            $entry = [
                'id' => (string) Str::uuid(),
                'event_id' => (string) $event->id,
                'title' => $report['title'],
                'sections' => $report['sections'],
                'table' => $report['table'],
                'format' => $format,
                'scope' => $scope,
                'mode' => $mode,
                'created_at' => now()->format('d/m/Y · H:i'),
            ];
            $this->pushHistory($entry);

            return back()->with('ai_report', $entry);
        } catch (\Throwable $e) {
            return back()->withInput()->with('ai_error', $e->getMessage());
        }
    }

    /** Ajoute une entrée en tête de l'historique IA (max 30 conservées). */
    protected function pushHistory(array $entry): void
    {
        $history = (array) Settings::get('report_ai_history', []);
        array_unshift($history, $entry);
        Settings::set('report_ai_history', array_slice($history, 0, 30));
    }

    public function xlsx(Event $event): Response
    {
        $report = new EventReport($event);
        $summary = $report->summary();

        $synthese = [
            ['Événement', $event->title ?: (string) $event->year],
            ['Participants', $summary['participants']],
            ['Communications', $summary['submissions']],
        ];
        foreach (Submission::statuses() as $key => $label) {
            $synthese[] = ['- '.$label, $summary['submissionsByStatus'][$key] ?? 0];
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

    public function pdf(Event $event): View
    {
        $report = new EventReport($event);

        return view('reports.edition', [
            'event' => $event,
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
     * axes) pour l'événement donné - mêmes clés/canvas que le tableau de bord.
     *
     * @return array<string, mixed>
     */
    protected function chartData(int $edId): array
    {
        $subs = fn () => Submission::query()->where('revision_of', 0)->where('event_id', $edId);
        $pay = fn () => Payment::query()->where('event_id', $edId);

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
