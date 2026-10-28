<?php

namespace App\Documents;

use App\Http\Controllers\AttestationController;
use App\Mail\ParticipantInvitationMail;
use App\Models\DocumentTemplate;
use App\Models\Participant;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Lettre d'invitation personnalisée, rendue en PDF depuis le MODÈLE ENREGISTRÉ
 * (type de document `invitation`, édité dans le concepteur). Jointe à
 * {@see ParticipantInvitationMail}.
 *
 * Même pipeline que le PDF d'une attestation ({@see AttestationController::pdf})
 * : modèle garanti par `ensureFor`, assaini, rendu par `LayoutRenderer` en mode
 * PDF (dompdf n'ira chercher ni image ni police distante), puis page à la taille
 * exacte de la mise en page (px → points à 96 dpi).
 */
class InvitationLetter
{
    public static function pdf(Participant $participant): \Barryvdh\DomPDF\PDF
    {
        $template = DocumentTemplate::ensureFor((int) $participant->event_id, 'attestation', 'invitation');

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', 'invitation');
        $canvas = LayoutRenderer::render($layout, InvitationVars::for($participant), pdf: true);

        $w = (int) $layout['width'];
        $h = (int) $layout['height'];

        return BrandedPdf::stamp(
            Pdf::loadView('pdf.invitation-letter', ['canvas' => $canvas])
                ->setPaper([0, 0, $w * 0.75, $h * 0.75]),
            'Invitation - '.$participant->displayName(),
        );
    }
}
