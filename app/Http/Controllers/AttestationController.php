<?php

namespace App\Http\Controllers;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use Illuminate\Contracts\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Attestations : document imprimable (avec QR de vérification) et page publique
 * de vérification par UUID.
 */
class AttestationController extends Controller
{
    /** Document imprimable de l'attestation (QR → page de vérification). */
    public function document(string $uuid): View
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        $verifyUrl = route('attestation.verify', $attestation->uuid);

        // Modèle personnalisé de l'édition pour ce type ? On rend la mise en page.
        $template = DocumentTemplate::where('edition_id', $attestation->edition_id)
            ->where('kind', 'attestation')->where('type', $attestation->type)->first();

        if ($template && $template->layout) {
            $layout = LayoutSanitizer::sanitize($template->layout, 'attestation', $attestation->type);
            $canvas = LayoutRenderer::render($layout, [
                'org' => $attestation->edition?->colloque?->name ?? '',
                'event' => $attestation->edition?->title ?? '',
                'year' => (string) ($attestation->edition?->year ?: ''),
                'location' => $attestation->edition?->location ?? '',
                'name' => (string) $attestation->recipient_name,
                'type' => mb_strtolower($attestation->typeLabel()),
                'date' => $attestation->issued_at?->format('d/m/Y') ?? '',
                'ref' => $attestation->uuid,
                'verify_url' => $verifyUrl,
            ]);

            return view('attestations.document-template', [
                'attestation' => $attestation,
                'canvas' => $canvas,
                'width' => $layout['width'],
                'verifyUrl' => $verifyUrl,
            ]);
        }

        $qr = QrCode::format('svg')->size(150)->margin(0)->generate($verifyUrl);

        return view('attestations.document', [
            'attestation' => $attestation,
            'qr' => $qr,
            'verifyUrl' => $verifyUrl,
        ]);
    }

    /** Page publique de vérification d'authenticité. */
    public function verify(string $uuid): View
    {
        $attestation = Attestation::with(['edition.colloque'])
            ->where('uuid', $uuid)->first();

        return view('attestations.verify', [
            'attestation' => $attestation,
        ]);
    }
}
