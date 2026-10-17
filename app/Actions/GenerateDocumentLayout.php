<?php

namespace App\Actions;

use App\AI\AiManager;
use App\Documents\LayoutSanitizer;
use App\Support\Branding;

/**
 * Génère la mise en page (JSON) d'un badge ou d'une attestation par IA à partir
 * d'une consigne de style (et d'une image d'inspiration optionnelle). L'IA ne
 * produit QUE le JSON ; la validation (LayoutSanitizer) et le rendu restent
 * déterministes.
 *
 * ⚠️ La consigne décrit TOUT ce que le concepteur sait rendre - types
 * d'éléments (dont `photo` et `signatures`), mise en forme du texte (police,
 * italique, souligné, capitales, interlignage, interlettrage), filets/cadres
 * arrondis, image de fond (le cadre officiel) et surtout la MAQUETTE OFFICIELLE
 * du type comme base à améliorer, avec les bons jetons de palette et les chemins
 * des logos/pictogrammes/cadres réels. Sans cela, l'IA produisait quatre
 * éléments génériques, sans cadre ni logo : un brouillon, pas un modèle.
 */
class GenerateDocumentLayout
{
    public function __construct(protected AiManager $manager) {}

    /**
     * @param  array<int, array{mime: string, data: string}>  $files
     * @return array<string, mixed>
     */
    public function handle(string $kind, string $type, string $prompt, array $files = []): array
    {
        $needsPdf = collect($files)->contains(fn ($f) => str_contains($f['mime'], 'pdf'));

        if (! $this->manager->resolve($needsPdf)) {
            throw new \RuntimeException('Aucun fournisseur d’IA n’est configuré.');
        }

        // Conception de maquette de document : palier qualité + bascule de fournisseur.
        $raw = $this->manager->extract($this->instruction($kind, $type, $prompt), $files, 'deep');

        return LayoutSanitizer::sanitize($raw, $kind, $type);
    }

    protected function instruction(string $kind, string $type, string $prompt): string
    {
        // Maquette officielle du type : sert de dimensions ET de BASE à améliorer,
        // pour conserver le cadre, les logos et le QR de la charte.
        $base = LayoutSanitizer::default($kind, $type);
        $w = $base['width'];
        $h = $base['height'];
        $baseJson = json_encode($base, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $fonts = implode(', ', array_keys(LayoutSanitizer::FONTS));
        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        if ($kind === 'badge') {
            $tokens = '[recipient.name], [recipient.role], [recipient.institution], '
                .'[edition.name], [edition.theme], [edition.dates], [edition.location], {ref}, {verify_url}';
            $assets = <<<ASSETS
            - Cadre de fond officiel (vert/bleu UK x Parakou) : "bg_image": "/img/badges/badge-default-bg.png"
            - Logo Université de Kara (image)      : "{$ukLogo}"
            - Logo Université de Parakou (image)   : "/img/attestations/logo-parakou.png"
            - Pictogramme lieu (image)             : "/img/badges/icon-location.png"
            - Pictogramme dates (image)            : "/img/badges/icon-calendar.png"
            ASSETS;
            $kindFr = 'badge nominatif';
            $advice = <<<'ADV'
            - Un badge se lit à distance : le NOM du porteur ([recipient.name]) est l'élément le plus grand (≈ 22 px), centré.
            - Garde les DEUX logos officiels côte à côte en en-tête, à la même hauteur.
            - Si la consigne demande une photo, ajoute un élément {"type":"photo", …} (carré ou rond via le rendu) entre l'en-tête et le nom, et DESCENDS le nom/rôle/thème pour lui faire la place.
            - Place un QR ({"type":"qr","value":"{verify_url}"}) dans un coin bas.
            - Lieu et dates vont en bas, précédés de leur pictogramme respectif.
            ADV;
        } else {
            $tokens = '[recipient.civility], [recipient.name], [edition.name], [edition.theme], [edition.dates], '
                .'[edition.location], [communication.title], [communication.type], [formation.title], '
                .'[signatory.name], [signatory.role], [certificate.issued_on], [certificate.uuid], {verify_url}';
            $assets = <<<ASSETS
            - Cadre officiel (motifs kente, charte UK x Parakou) : "bg_image": "/img/attestations/attestation-communication-bg.png"
            - Cadre « formation » (coins colorés)                : "bg_image": "/img/attestations/attestation-formation-bg.png"
            - Logo Université de Kara (image)                    : "{$ukLogo}"
            - Logo Université de Parakou (image)                 : "/img/attestations/logo-parakou.png"
            ASSETS;
            $kindFr = 'attestation scientifique';
            $advice = <<<'ADV'
            - Le TITRE (ex. « ATTESTATION DE … ») est en police "times", en capitales, centré, grand (≈ 28 px).
            - Le corps est en police "lato", ≈ 14 px, interligne ≈ 1.4.
            - Conserve les deux logos officiels en en-tête, le QR et le bloc signataire ([signatory.role] puis [signatory.name]).
            - Pour une attestation de formation, tu peux ajouter un élément {"type":"signatures", …} à gauche du signataire.
            ADV;
        }

        return <<<TXT
        Tu es un designer graphique spécialisé dans les documents académiques. Tu conçois la mise
        en page d'un(e) {$kindFr} pour l'Université de Kara. Réponds par un objet JSON STRICT et
        RIEN d'autre (aucun texte, aucun commentaire, aucun bloc de code autour).

        CANVAS : width={$w}, height={$h} (pixels). Toutes les coordonnées restent dans ces bornes.

        OBJET attendu :
        { "width": int, "height": int, "bg": "#rrggbb", "accent": "#rrggbb",
          "bg_image": "/chemin/…" (optionnel), "elements": [ … ] }

        TYPES D'ÉLÉMENTS autorisés (uniquement ceux-ci) :
        - {"type":"text","x","y","w","h","text":string,"size":int(6-140),"weight":int(300-900),
           "color":"#rrggbb","align":"left|center|right","font":"<clé>","italic":bool,
           "underline":bool,"upper":bool(capitales),"leading":float(0.8-3.0),"spacing":float(interlettrage px)}
        - {"type":"image","x","y","w","h","src":"/chemin/d/un/asset/fourni"}
        - {"type":"photo","x","y","w","h"}  (emplacement de la photo du porteur ; aucune source)
        - {"type":"qr","x","y","size":int(24-400),"value":string}
        - {"type":"rect","x","y","w","h","color":"#rrggbb","fill":bool,"radius":int(coins arrondis),
           "border":int(épaisseur du filet),"border_color":"#rrggbb"}
        - {"type":"signatures","x","y","w","h","size":int,"names":bool}  (bloc de signatures)

        POLICES (clé exacte dans "font") : {$fonts}.

        JETONS (écris-les TELS QUELS dans "text"/"value" ; ils seront remplacés à l'émission -
        n'invente jamais de vraies valeurs à leur place) :
        {$tokens}

        IMAGES : n'utilise QUE ces chemins d'assets réels (ne JAMAIS inventer une URL d'image) :
        {$assets}

        PRINCIPES :
        {$advice}
        - Charte Université de Kara : bleu #223ea4, or #ffd700, vert #009933. Reste sobre et lisible.
        - Respecte une hiérarchie claire et des marges régulières.

        BASE À AMÉLIORER : voici la maquette officielle actuelle de ce document. Pars d'elle,
        garde le cadre (bg_image), les logos officiels, le QR et les jetons, puis adapte la
        composition, les couleurs et la typographie selon la consigne. Ne repars PAS d'une page
        blanche sauf si la consigne le demande explicitement.
        {$baseJson}

        CONSIGNE DE STYLE : {$prompt}
        TXT;
    }
}
