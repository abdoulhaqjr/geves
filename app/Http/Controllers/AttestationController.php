<?php

namespace App\Http\Controllers;

use App\Documents\AttestationCertifier;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Tout document vient d'un modèle ENREGISTRÉ (créé au besoin) : plus de
        // repli implicite à la charte, que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $attestation->edition_id, 'attestation', (string) $attestation->type);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
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
            // Communication concernée (attestation de communication) - vide pour les autres types.
            'communication' => (string) (($attestation->meta ?? [])['communication_title'] ?? ''),
            'communication_ref' => (string) (($attestation->meta ?? [])['communication_ref'] ?? ''),
            // Formation concernée (attestation de formation / animation) - vide sinon.
            'formation' => (string) (($attestation->meta ?? [])['formation_title'] ?? ''),
            'formation_theme' => (string) (($attestation->meta ?? [])['formation_theme'] ?? ''),
            'formateurs' => implode(', ', (array) (($attestation->meta ?? [])['formation_trainers'] ?? [])),
            // Signatures figées à l'émission (élément « signatures » du concepteur).
            'signatures' => (array) (($attestation->meta ?? [])['formation_signatures'] ?? []),
        ]);

        return view('attestations.document-template', [
            'attestation' => $attestation,
            'canvas' => $canvas,
            'width' => $layout['width'],
            'verifyUrl' => $verifyUrl,
        ]);
    }

    /** Attestation en vrai PDF serveur (téléchargement, format A4 paysage). */
    public function pdf(string $uuid): Response
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        $verifyUrl = route('attestation.verify', $attestation->uuid);

        // Même règle que l'aperçu : le PDF rend toujours un modèle enregistré.
        $template = DocumentTemplate::ensureFor((int) $attestation->edition_id, 'attestation', (string) $attestation->type);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
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
            // Communication concernée (attestation de communication) - vide pour les autres types.
            'communication' => (string) (($attestation->meta ?? [])['communication_title'] ?? ''),
            'communication_ref' => (string) (($attestation->meta ?? [])['communication_ref'] ?? ''),
            // Formation concernée (attestation de formation / animation) - vide sinon.
            'formation' => (string) (($attestation->meta ?? [])['formation_title'] ?? ''),
            'formation_theme' => (string) (($attestation->meta ?? [])['formation_theme'] ?? ''),
            'formateurs' => implode(', ', (array) (($attestation->meta ?? [])['formation_trainers'] ?? [])),
            // Signatures figées à l'émission (élément « signatures » du concepteur).
            'signatures' => (array) (($attestation->meta ?? [])['formation_signatures'] ?? []),
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

    /** Page publique de vérification d'authenticité. */
    public function verify(string $uuid, AttestationCertifier $certifier): View
    {
        $attestation = Attestation::with(['edition.colloque'])
            ->where('uuid', $uuid)->first();

        // Vérification de la signature cryptographique (au-delà du registre).
        $proof = $attestation ? $certifier->verify($attestation) : null;

        return view('attestations.verify', [
            'attestation' => $attestation,
            'proof' => $proof,
        ]);
    }

    /**
     * Verifiable Credential signé, téléchargeable en JSON (JWS compact + VC
     * décodé pour lisibilité). Permet une vérification par un tiers.
     */
    public function credential(string $uuid, AttestationCertifier $certifier): JsonResponse
    {
        $attestation = Attestation::with(['edition.colloque'])
            ->where('uuid', $uuid)->firstOrFail();

        abort_unless($attestation->isSigned(), 404);

        return response()->json([
            'format' => 'vc+jwt',
            'alg' => AttestationCertifier::ALG,
            'jws' => $attestation->credential,
            'credential' => $certifier->buildCredential($attestation),
            'verify_url' => route('attestation.verify', $attestation->uuid),
        ], 200, [
            'Content-Disposition' => 'attachment; filename="attestation-'.$attestation->uuid.'.json"',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /** Document DID (did:web) publiant la clé publique de l'émetteur. */
    public function didDocument(AttestationCertifier $certifier): JsonResponse
    {
        $document = $certifier->didDocument();

        abort_if($document === null, 404);

        return response()->json($document, 200, [], JSON_UNESCAPED_SLASHES);
    }

    /** JWKS de la clé publique de l'émetteur (vérification JOSE générique). */
    public function jwks(AttestationCertifier $certifier): JsonResponse
    {
        $jwks = $certifier->jwks();

        abort_if($jwks === null, 404);

        return response()->json($jwks, 200, [], JSON_UNESCAPED_SLASHES);
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
