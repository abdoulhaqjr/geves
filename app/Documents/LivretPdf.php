<?php

namespace App\Documents;

use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Dompdf\Canvas;
use Dompdf\FontMetrics;
use Illuminate\Support\Collection;

/** Rend le même livret PDF pour l'administration et l'espace participant. */
final class LivretPdf
{
    /**
     * Pages à RÉSERVER pour les documents ajoutés, par point d'insertion
     * ({@see LivretAssembly::reserves()}). Vide, le livre est rendu tel quel.
     *
     * @param  array<string, list<array{uri: string, pages: int}>>  $reserves
     */
    public static function make(Event $event, Collection $groups, array $reserves = []): PDF
    {
        // Un livre complet peut compter plusieurs centaines de résumés. Le rendu
        // Dompdf dépasse alors la limite HTTP PHP de 30 secondes, avant même la
        // fusion éventuelle de sa couverture DocSpace.
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $data = LivretView::data($event, $groups);
        // ⚠️ Les emplacements réservés entrent dans le FLUX du document, pas
        // dans ses données : ils doivent être rendus avant que dompdf ne
        // numérote les pages, sans quoi la réservation ne servirait à rien.
        $data['inserts'] = $reserves;
        $pdf = BrandedPdf::stamp(
            PdfFacade::loadView('documents.livret-pdf', $data)->setPaper('a4'),
            'Livre des résumés - '.$event->title,
        );

        if ($data['opts']['page_numbers']) {
            // Le pied fixe de dompdf se répète aussi sur la couverture. Le canvas
            // permet de ne numéroter que les pages 2 et suivantes.
            $pdf->render();
            $pdf->getDomPDF()->getCanvas()->page_script(static function (
                int $pageNumber,
                int $pageCount,
                Canvas $canvas,
                FontMetrics $fontMetrics,
            ): void {
                if ($pageNumber === 1) {
                    return;
                }

                $label = (string) $pageNumber;
                $font = $fontMetrics->getFont('Helvetica');
                $size = 9;
                $x = ($canvas->get_width() - $canvas->get_text_width($label, $font, $size)) / 2;
                $canvas->text($x, $canvas->get_height() - 40, $label, $font, $size, [107 / 255, 114 / 255, 128 / 255]);
            });
        }

        return $pdf;
    }
}
