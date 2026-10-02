<?php

namespace App\Http\Controllers;

use App\Documents\AttestationCertifier;
use App\Documents\AttestationVars;
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
    /**
     * Page publique de l'attestation : le document tel qu'il a été délivré, son
     * état de vérification, l'émetteur et ce que l'attestation certifie.
     *
     * C'est l'adresse que porte le QR code et que le bénéficiaire partage : elle
     * doit tenir debout toute seule devant un recruteur, pas se contenter
     * d'afficher une image imprimable.
     */
    public function document(string $uuid, AttestationCertifier $certifier): View
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        // ⚠️ Le QR du document encode cette adresse. Il menait à la page de
        // vérification seule : on scannait une attestation pour atterrir sur un
        // verdict, sans le document. Il mène désormais à la page de l'attestation,
        // qui porte le document ET la vérification. `/verifier/{uuid}` reste
        // valable - les QR déjà imprimés continuent de fonctionner.
        $verifyUrl = route('attestation.document', $attestation->uuid);

        // Tout document vient d'un modèle ENREGISTRÉ (créé au besoin) : plus de
        // repli implicite à la charte, que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $attestation->edition_id, 'attestation', (string) $attestation->type);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
        $canvas = LayoutRenderer::render($layout, AttestationVars::for($attestation, $verifyUrl));

        return view('attestations.document-template', [
            'attestation' => $attestation,
            'canvas' => $canvas,
            'width' => $layout['width'],
            // Même preuve que la page de vérification : les deux doivent dire la
            // même chose d'une même attestation.
            'proof' => $certifier->verify($attestation),
            'facts' => $this->documentFacts($attestation),
        ]);
    }

    /**
     * Ce que l'attestation certifie, en lignes lisibles - vide écarté, pour ne
     * pas afficher d'intitulé sans valeur.
     *
     * @return array<string, string>
     */
    private function documentFacts(Attestation $attestation): array
    {
        $edition = $attestation->edition;
        $meta = (array) ($attestation->meta ?? []);

        return array_filter([
            'Type' => $attestation->typeLabel(),
            'Événement' => (string) ($edition?->title ?? ''),
            'Thème' => (string) ($edition?->theme ?? ''),
            'Dates' => (string) ($edition?->dateRangeLabel() ?? ''),
            'Lieu' => (string) ($edition?->location ?? ''),
            'Communication' => (string) ($meta['communication_title'] ?? ''),
            'Formation' => (string) ($meta['formation_title'] ?? ''),
        ], fn (string $v) => $v !== '');
    }

    /** Attestation en vrai PDF serveur (téléchargement, format A4 paysage). */
    public function pdf(string $uuid): Response
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        // ⚠️ Le QR du document encode cette adresse. Il menait à la page de
        // vérification seule : on scannait une attestation pour atterrir sur un
        // verdict, sans le document. Il mène désormais à la page de l'attestation,
        // qui porte le document ET la vérification. `/verifier/{uuid}` reste
        // valable - les QR déjà imprimés continuent de fonctionner.
        $verifyUrl = route('attestation.document', $attestation->uuid);

        // Même règle que l'aperçu : le PDF rend toujours un modèle enregistré.
        $template = DocumentTemplate::ensureFor((int) $attestation->edition_id, 'attestation', (string) $attestation->type);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
        $canvas = LayoutRenderer::render($layout, AttestationVars::for($attestation, $verifyUrl), pdf: true);

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
     * Vérification en direct, pour la page de l'attestation elle-même.
     *
     * ⚠️ Le bouton « Vérifier » envoyait le visiteur sur une autre adresse : il
     * quittait le document pour lire un verdict, et devait revenir. La
     * vérification se fait donc **sur place**, et elle est *refaite* à la demande
     * (registre + signature) - ce n'est pas un repli du texte déjà affiché.
     */
    public function verifyJson(string $uuid, AttestationCertifier $certifier): JsonResponse
    {
        $attestation = Attestation::with(['edition.colloque'])->where('uuid', $uuid)->first();

        if (! $attestation) {
            return response()->json([
                'found' => false,
                'status' => 'unknown',
                'label' => __('Attestation introuvable'),
                'detail' => __('Aucune attestation ne correspond à cet identifiant.'),
                'checked_at' => now()->translatedFormat('j F Y à H:i'),
            ], 404);
        }

        $proof = $certifier->verify($attestation);
        $revoked = (bool) $attestation->revoked;
        $signed = (bool) $proof['signed'];
        $valid = (bool) $proof['valid'];

        [$status, $label, $detail] = match (true) {
            $revoked => ['revoked', __('Attestation révoquée'), __('L’émetteur a retiré ce document : il ne vaut plus comme preuve.')],
            $signed && ! $valid => ['warn', __('Signature non validée'), __('Le document existe au registre, mais sa signature cryptographique n’a pas pu être validée.')],
            $valid => ['ok', __('Attestation authentique'), __('Signature cryptographique vérifiée auprès de la clé publique de l’émetteur.')],
            default => ['ok', __('Attestation authentique'), __('Inscrite au registre officiel de l’émetteur.')],
        };

        return response()->json([
            'found' => true,
            'status' => $status,
            'label' => $label,
            'detail' => $detail,
            'checked_at' => now()->translatedFormat('j F Y à H:i'),
            'facts' => array_filter([
                __('Bénéficiaire') => (string) $attestation->recipient_name,
                __('Type') => $attestation->typeLabel(),
                __('Émetteur') => (string) ($attestation->edition?->colloque?->name ?? ''),
                __('Émise le') => $attestation->issued_at?->translatedFormat('j F Y') ?? '',
                __('Identifiant') => $attestation->uuid,
                __('Signée par') => $valid ? (string) ($proof['payload']['iss'] ?? '') : '',
            ], fn (string $v) => $v !== ''),
            'credential_url' => $valid ? route('attestation.credential', $attestation->uuid) : null,
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
