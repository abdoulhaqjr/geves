<?php

namespace App\Documents;

use Barryvdh\DomPDF\PDF;

/** Métadonnées communes aux PDF générés par la plateforme. */
final class BrandedPdf
{
    public static function stamp(PDF $pdf, string $title): PDF
    {
        $platform = (string) config('app.name', 'GEVES');

        return $pdf->addInfo([
            'Title' => $title.' · '.$platform,
            'Author' => $platform.' · Université de Kara',
            'Creator' => $platform,
            'Subject' => $title,
        ]);
    }
}
