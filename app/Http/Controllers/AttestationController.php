<?php

namespace App\Http\Controllers;

use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;

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
                // Communication concernée (attestation de communication) — vide pour les autres types.
                'communication' => (string) (($attestation->meta ?? [])['communication_title'] ?? ''),
                'communication_ref' => (string) (($attestation->meta ?? [])['communication_ref'] ?? ''),
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

    /** Attestation en vrai PDF serveur (téléchargement, format A4 paysage). */
    public function pdf(string $uuid): Response
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        $verifyUrl = route('attestation.verify', $attestation->uuid);

        // Modèle personnalisé enregistré pour ce type ? Le PDF rend cette mise en page.
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
                // Communication concernée (attestation de communication) — vide pour les autres types.
                'communication' => (string) (($attestation->meta ?? [])['communication_title'] ?? ''),
                'communication_ref' => (string) (($attestation->meta ?? [])['communication_ref'] ?? ''),
            ]);

            // Page à la taille exacte de la mise en page (px → points à 96 dpi).
            $w = (int) $layout['width'];
            $h = (int) $layout['height'];

            return Pdf::loadView('pdf.attestation-template', [
                'attestation' => $attestation,
                'canvas' => $canvas,
            ])->setPaper([0, 0, $w * 0.75, $h * 0.75])
                ->download('attestation-'.$attestation->uuid.'.pdf');
        }

        // Repli : mise en page par défaut (A4 paysage).
        $qr = QrCode::format('svg')->size(150)->margin(0)->generate($verifyUrl);

        return Pdf::loadView('pdf.attestation', [
            'attestation' => $attestation,
            'qr' => $qr,
            'verifyUrl' => $verifyUrl,
        ])->setPaper('a4', 'landscape')
            ->download('attestation-'.$attestation->uuid.'.pdf');
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

    /**
     * Landing publique : saisie manuelle d'un code d'attestation (le QR imprimé
     * pointe déjà directement vers la page de vérification). Redirige vers le
     * résultat si un code est fourni.
     */
    public function verifyLanding(Request $request): View|RedirectResponse
    {
        $code = trim((string) $request->query('code', ''));

        if ($code !== '') {
            // Accepte un UUID brut ou une URL de vérification collée.
            if (preg_match('~([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})~i', $code, $m)) {
                $code = $m[1];
            }

            return redirect()->route('attestation.verify', $code);
        }

        return view('attestations.verify-landing');
    }
}
