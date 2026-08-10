<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\Submission;
use App\Support\EditionReport;
use App\Support\XlsxWriter;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exports de reporting d'une édition : classeur Excel multi-feuilles (.xlsx,
 * PHP pur) et rapport PDF imprimable (HTML + @media print).
 */
class ReportController extends Controller
{
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
}
