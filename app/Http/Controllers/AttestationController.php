<?php

namespace App\Http\Controllers;

use App\Actions\RecordAttestationEvent;
use App\Documents\AttestationCertifier;
use App\Documents\AttestationVars;
use App\Documents\LayoutRenderer;
use App\Documents\LayoutSanitizer;
use App\Models\Attestation;
use App\Models\DocumentTemplate;
use App\Support\AttestationPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Attestations : document imprimable (avec QR) et page publique du document, qui
 * porte sa propre vérification.
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
    public function document(string $uuid, AttestationCertifier $certifier, Request $request, RecordAttestationEvent $constate): View
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        // ⚠️ **Délivrer n'est pas être reçu.** Rien ne disait si une attestation
        // en ligne était jamais arrivée à son destinataire : émise, notifiée et
        // jamais ouverte, elle était indiscernable d'une attestation lue et
        // partagée. Le geste est constaté ici, à la seule adresse par laquelle on
        // consulte une attestation. Le titulaire connecté pose du même coup son
        // accusé de réception ; le staff ne compte pas (RecordAttestationEvent).
        $constate($attestation, 'view', $request);

        // ⚠️ Le QR du document encode cette adresse. Il menait à une page de
        // vérification seule : on scannait une attestation pour atterrir sur un
        // verdict, sans le document. Il mène à la page de l'attestation, qui
        // porte le document ET sa vérification.
        $verifyUrl = route('attestation.document', $attestation->uuid);

        // Tout document vient d'un modèle ENREGISTRÉ (créé au besoin) : plus de
        // repli implicite à la charte, que l'admin ne pouvait ni voir ni modifier.
        $template = DocumentTemplate::ensureFor((int) $attestation->edition_id, 'attestation', (string) $attestation->type);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
        $canvas = LayoutRenderer::render($layout, AttestationVars::for($attestation, $verifyUrl));

        return view('attestations.document-template', [
            'attestation' => $attestation,
            'canvas' => $canvas,
            // ⚠️ Délivrance PHYSIQUE : l'exemplaire qui fait foi est celui qu'on
            // remet en main propre. La page montre donc un SPÉCIMEN - il dit ce que
            // l'attestation certifie et se vérifie, mais il ne se télécharge ni ne
            // se partage comme l'original. Sans la marque, deux documents
            // identiques circuleraient, dont un que l'émetteur n'a jamais remis.
            'papier' => AttestationPolicy::isPaper($attestation->edition),
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
    public function pdf(string $uuid, Request $request, RecordAttestationEvent $constate): Response
    {
        $attestation = Attestation::with(['edition.colloque', 'participant'])
            ->where('uuid', $uuid)->firstOrFail();

        // ⚠️ En délivrance physique, le PDF n'est pas public : l'original est
        // imprimé et remis par le secrétariat. Le laisser se télécharger ici
        // viderait le choix de son sens - deux exemplaires circuleraient, dont un
        // que personne n'a remis.
        // ⚠️ Le BACK-OFFICE garde l'accès : c'est lui qui imprime (cf. le lot
        // d'AttestationExportController). Lui fermer la porte reviendrait à
        // interdire la délivrance qu'on vient de choisir.
        abort_if(
            AttestationPolicy::isPaper($attestation->edition) && ! Auth::guard('web')->check(),
            403,
            'Cette attestation est délivrée sur papier : son exemplaire officiel est remis par le secrétariat.',
        );

        // ⚠️ Le QR du document encode cette adresse. Il menait à une page de
        // vérification seule : on scannait une attestation pour atterrir sur un
        // verdict, sans le document. Il mène à la page de l'attestation, qui
        // porte le document ET sa vérification.
        $verifyUrl = route('attestation.document', $attestation->uuid);

        // Même règle que l'aperçu : le PDF rend toujours un modèle enregistré.
        $template = DocumentTemplate::ensureFor((int) $attestation->edition_id, 'attestation', (string) $attestation->type);

        $layout = LayoutSanitizer::sanitize((array) $template->layout, 'attestation', $attestation->type);
        $canvas = LayoutRenderer::render($layout, AttestationVars::for($attestation, $verifyUrl), pdf: true);

        // Page à la taille exacte de la mise en page (px → points à 96 dpi).
        $w = (int) $layout['width'];
        $h = (int) $layout['height'];

        // Emporter le document, c'est l'avoir ouvert : le journal distingue les
        // deux gestes, la synthèse les additionne sous « ouverte ». Constaté
        // APRÈS le rendu : un PDF qui échoue n'a été emporté par personne.
        $constate($attestation, 'download', $request);

        return Pdf::loadView('pdf.attestation-template', [
            'attestation' => $attestation,
            'canvas' => $canvas,
        ])->setPaper([0, 0, $w * 0.75, $h * 0.75])
            ->download('attestation-'.$attestation->uuid.'.pdf');
    }

    /**
     * **Gestes posés dans la page** : partage mené à son terme, lien copié,
     * départ vers « Ajouter au profil » de LinkedIn.
     *
     * ⚠️ Le serveur ne peut pas les voir : ils se passent dans le navigateur, et
     * le partage comme la copie n'émettent aucune requête. La page les déclare
     * donc ici - c'est le SEUL moyen de répondre à « l'a-t-il partagée ? ».
     *
     * ⚠️ **Débit limité et liste de gestes fermée** : l'adresse est publique, et
     * sans garde n'importe qui gonflerait les compteurs d'une attestation
     * nominative. Le geste inconnu est refusé, jamais journalisé « au cas où ».
     */
    public function trace(string $uuid, Request $request, RecordAttestationEvent $constate): JsonResponse
    {
        $attestation = Attestation::where('uuid', $uuid)->firstOrFail();

        $geste = (string) $request->input('kind', '');

        // Seuls les gestes que la page peut honnêtement déclarer : l'ouverture et
        // le téléchargement, eux, sont constatés côté serveur et ne se déclarent
        // pas - sinon on pourrait s'inventer des lectures.
        if (! in_array($geste, ['share', 'copy', 'linkedin'], true)) {
            return response()->json(['ok' => false], 422);
        }

        $constate($attestation, $geste, $request);

        return response()->json(['ok' => true]);
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
            'verify_url' => route('attestation.document', $attestation->uuid),
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
     * mène directement à l'attestation). Un code valable conduit à la page de
     * l'attestation, qui porte le document et sa vérification.
     */
    public function verifyLanding(Request $request): View|RedirectResponse
    {
        $code = trim((string) $request->query('code', ''));

        if ($code !== '') {
            // Accepte un UUID brut ou une URL d'attestation collée.
            if (preg_match('~([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})~i', $code, $m)) {
                $code = $m[1];
            }

            // ⚠️ Un code inconnu ne mérite pas un 404 : il a été SAISI ici, et la
            // faute de frappe se corrige ici. On reste donc sur le formulaire, le
            // code saisi sous les yeux, plutôt que d'envoyer sur une page absente.
            if (! Attestation::where('uuid', $code)->exists()) {
                return view('attestations.verify-landing', [
                    'erreur' => __('Aucune attestation ne correspond à ce code.'),
                ]);
            }

            return redirect()->route('attestation.document', $code);
        }

        return view('attestations.verify-landing');
    }
}
