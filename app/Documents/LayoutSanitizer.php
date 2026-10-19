<?php

namespace App\Documents;

use App\Support\Branding;

/**
 * Validation déterministe d'une mise en page (badge / attestation). Quelle que
 * soit la sortie de l'IA, on borne les coordonnées, on liste blanche les types
 * d'éléments et leurs propriétés, et on retombe sur une mise en page par défaut
 * si le résultat est vide/invalide. Le rendu (LayoutRenderer) reste ainsi sûr.
 */
class LayoutSanitizer
{
    public const KINDS = ['badge', 'attestation'];

    /** `signatures` = bloc des signatures des formateurs (attestations de formation). */
    public const ELEMENT_TYPES = ['text', 'image', 'photo', 'qr', 'rect', 'signatures'];

    /**
     * Polices proposées par le concepteur, avec leur pile CSS. ⚠️ Liste blanche :
     * la valeur enregistrée est une CLÉ, jamais une déclaration CSS libre - un
     * `font-family` venu du navigateur finirait dans un attribut `style`.
     */
    public const FONTS = [
        'jost' => "'Jost', system-ui, sans-serif",
        'georgia' => "Georgia, 'Times New Roman', serif",
        'playfair' => "'Playfair Display', Georgia, serif",
        'arial' => 'Arial, Helvetica, sans-serif',
        'lato' => "'Lato', system-ui, sans-serif",
        // Times : rendu identique écran (Times New Roman) / PDF (Times natif de dompdf),
        // sans fonte à héberger. Police du titre des attestations officielles.
        'times' => "'Times New Roman', Times, serif",
    ];

    /** @return array<string, mixed> */
    public static function sanitize(array $raw, string $kind, string $type = 'default'): array
    {
        $default = self::default($kind, $type);

        $w = self::clampInt($raw['width'] ?? null, 120, 2000, $default['width']);
        $h = self::clampInt($raw['height'] ?? null, 120, 2000, $default['height']);
        $bg = self::color($raw['bg'] ?? null, $default['bg']);
        $accent = self::color($raw['accent'] ?? null, $default['accent']);

        $elements = [];
        foreach ((array) ($raw['elements'] ?? []) as $el) {
            $t = is_array($el) ? ($el['type'] ?? '') : '';
            if (! in_array($t, self::ELEMENT_TYPES, true)) {
                continue;
            }

            $e = [
                'type' => $t,
                'x' => self::clampInt($el['x'] ?? 0, 0, $w, 0),
                'y' => self::clampInt($el['y'] ?? 0, 0, $h, 0),
            ];

            if ($t === 'qr') {
                $e['size'] = self::clampInt($el['size'] ?? 96, 24, 400, 96);
                $e['value'] = mb_substr((string) ($el['value'] ?? ''), 0, 200);
            } else {
                $minimum = $t === 'rect' ? 1 : 4;
                $e['w'] = self::clampInt($el['w'] ?? 100, $minimum, $w, 100);
                $e['h'] = self::clampInt($el['h'] ?? 30, $minimum, $h, 30);
            }

            if ($t === 'text') {
                $e['text'] = mb_substr((string) ($el['text'] ?? ''), 0, 500);
                $e['size'] = self::clampInt($el['size'] ?? 16, 6, 140, 16);
                $e['weight'] = self::clampInt($el['weight'] ?? 500, 300, 900, 500);
                $e['color'] = self::color($el['color'] ?? null, '#1a2332');
                $align = $el['align'] ?? 'left';
                $e['align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
                // ⚠️ Mise en forme du concepteur : police, italique, souligné,
                // interlignage, interlettrage, capitales. Sans elles, un titre
                // dessiné en Playfair italique ressortait en Jost droit.
                $font = (string) ($el['font'] ?? 'jost');
                $e['font'] = isset(self::FONTS[$font]) ? $font : 'jost';
                $e['italic'] = (bool) ($el['italic'] ?? false);
                $e['underline'] = (bool) ($el['underline'] ?? false);
                $e['upper'] = (bool) ($el['upper'] ?? false);
                $e['leading'] = self::clampFloat($el['leading'] ?? 1.2, 0.8, 3.0, 1.2);
                $e['spacing'] = self::clampFloat($el['spacing'] ?? 0, -5, 20, 0);
            } elseif ($t === 'rect') {
                $e['shape'] = in_array($el['shape'] ?? null, ['line-h', 'line-v'], true) ? $el['shape'] : 'rect';
                $e['color'] = self::color($el['color'] ?? null, $accent);
                $e['fill'] = (bool) ($el['fill'] ?? true);
                // Un filet ou un cadre arrondi devenait un aplat rectangulaire.
                $e['radius'] = self::clampInt($el['radius'] ?? 0, 0, 400, 0);
                $e['border'] = self::clampInt($el['border'] ?? 0, 0, 40, 0);
                $e['border_color'] = self::color($el['border_color'] ?? null, $accent);
            } elseif ($t === 'image') {
                $e['src'] = self::src($el['src'] ?? '');
                if (in_array($e['src'], ['/img/badges/icon-location.svg', '/img/badges/icon-calendar.svg', '/img/badges/icon-location.png', '/img/badges/icon-calendar.png'], true)) {
                    $e['color'] = self::color($el['color'] ?? null, '#2e3440');
                }
            } elseif ($t === 'signatures') {
                $e['size'] = self::clampInt($el['size'] ?? 11, 6, 40, 11);
                $e['names'] = (bool) ($el['names'] ?? true);
            }

            $elements[] = $e;
        }

        // ⚠️ L'image de fond n'était pas enregistrée : une attestation dessinée
        // sur un fond illustré ressortait sur du blanc.
        $bgImage = self::src($raw['bg_image'] ?? '');

        $usedDefaultElements = $elements === [];
        if ($usedDefaultElements) {
            // Rien d'exploitable (IA muette, JSON vide) : on retombe sur la maquette
            // officielle - ET son cadre. Sans ce second repli, les éléments par défaut
            // flottaient sur un fond blanc parce que l'IA avait omis `bg_image`.
            $elements = $default['elements'];
            if ($bgImage === '') {
                $bgImage = (string) ($default['bg_image'] ?? '');
            }
        }

        return [
            'width' => $w, 'height' => $h, 'bg' => $bg, 'accent' => $accent,
            'bg_image' => $bgImage,
            'erase_default_rule' => $kind === 'badge' && $bgImage === '/img/badges/badge-default-bg.png'
                && (bool) ($raw['erase_default_rule'] ?? $usedDefaultElements),
            'elements' => $elements,
        ];
    }

    /** Borne un nombre décimal (interlignage, interlettrage). */
    protected static function clampFloat(mixed $v, float $min, float $max, float $def): float
    {
        if (! is_numeric($v)) {
            return $def;
        }

        return round(max($min, min($max, (float) $v)), 2);
    }

    /**
     * Source d'image acceptée : URL absolue **ou** chemin servi par le site.
     *
     * ⚠️ Seules les URL absolues passaient : un logo téléversé, référencé en
     * `/storage/…`, était rejeté à l'enregistrement et disparaissait du document.
     * `data:` reste refusé : le concepteur téléverse désormais ses images
     * (`templates.upload`) et n'enregistre que leur adresse - un document n'a
     * pas à embarquer ses octets, et dompdf lit le fichier sur le disque.
     */
    protected static function src(mixed $raw): string
    {
        $src = trim((string) $raw);

        if ($src === '' || str_starts_with($src, 'data:')) {
            return '';
        }

        if (str_starts_with($src, '/')) {
            return mb_substr($src, 0, 500);
        }

        return (string) (filter_var($src, FILTER_VALIDATE_URL) ?: '');
    }

    /**
     * Mise en page par défaut fonctionnelle (utilisée si aucun modèle enregistré).
     *
     * ⚠️ Le défaut dépend du TYPE d'attestation : l'attestation de communication a
     * son propre modèle (charte UK/Parakou, cadre décoratif), qui doit rester
     * identique à celui que charge le concepteur (`loadTemplate('communication')`
     * dans `template-designer.js`). Les deux sources décrivent la même maquette :
     * l'une sert au rendu quand aucun modèle n'est enregistré, l'autre à l'édition.
     */
    public static function default(string $kind, string $type = 'default'): array
    {
        if ($kind === 'badge') {
            return self::defaultBadge($type === 'participant_photo');
        }

        return match ($type) {
            'communication' => self::attestationCommunication(),
            'participation' => self::attestationParticipation(),
            'moderation' => self::attestationModeration(),
            'rapporteur' => self::attestationRapporteur(),
            'organisation' => self::attestationOrganisation(),
            'comite_organisation' => self::attestationComiteOrganisation(),
            'instruction_resumes' => self::attestationInstructionResumes(),
            'formation' => self::attestationFormation(),
            'formation_animation' => self::attestationFormationAnimation(),
            default => self::defaultAttestation(),
        };
    }

    /**
     * Formats de porte-badge pour lesquels la maquette officielle est composée.
     * Largeur × hauteur en px à 96 dpi (1 px = 1/96 pouce).
     */
    public const BADGE_FORMATS = [
        'modele-303' => [333, 472],  // 88 × 125 mm
        'modele-304' => [378, 510],  // 100 × 135 mm
        'modele-106' => [246, 359],  // 65 × 95 mm
        'a6-pochette' => [408, 586], // 108 × 155 mm
        'cr80' => [324, 204],        // 85,6 × 54 mm (PAYSAGE : maquette de carte)
    ];

    /**
     * Maquette officielle du badge, COMPOSÉE pour le format demandé.
     *
     * ⚠️⚠️ **Les trois porte-badges n'ont pas le même rapport** (303 : 0,704 ;
     * 304 : 0,741 ; 106 : 0,684). Agrandir le 303 pour obtenir les autres les
     * DÉFORMERAIT - un cadre plus large étiré en hauteur, un QR ovalisé. La
     * maquette est donc décrite **une seule fois en proportions** et composée pour
     * chaque format : positions et boîtes suivent chacune leur axe, tandis que les
     * **carrés (QR, photo, pictogrammes) et les corps de texte suivent le PLUS
     * PETIT rapport** - sinon le QR cesserait d'être carré et le texte déborderait.
     *
     * ⚠️ Le 303 sort EXACTEMENT comme avant cette composition (mêmes entiers) :
     * c'est la référence, et `BadgeDefaultTemplateTest` la gèle.
     *
     * @return array<string, mixed>
     */
    protected static function defaultBadge(bool $withPhoto = false, string $format = 'modele-303'): array
    {
        [$w, $h] = self::BADGE_FORMATS[$format] ?? self::BADGE_FORMATS['modele-303'];

        // ⚠️ Un badge PAYSAGE (CR80) ne se compose pas comme un portrait : la pile
        // verticale - logos, événement, nom, rôle, thème, pied - n'y tient pas. On
        // bascule sur une maquette de CARTE, pensée pour cette largeur.
        return $w > $h
            ? self::badgeCarte($w, $h, $withPhoto)
            : self::badgePortrait($w, $h, $withPhoto);
    }

    /**
     * Maquette PORTRAIT (modèles 303, 304, 106, A6 porte-badge), décrite en
     * proportions et composée pour le format demandé.
     *
     * @return array<string, mixed>
     */
    private static function badgePortrait(int $w, int $h, bool $withPhoto): array
    {

        $dark = '#2e3440';

        // Proportions de la maquette : fractions de la largeur et de la hauteur.
        $fx = fn (float $f): int => (int) round($f * $w);
        $fy = fn (float $f): int => (int) round($f * $h);

        // ⚠️ Un seul rapport pour ce qui doit rester carré ou lisible.
        $r = min($w / 333, $h / 472);
        $sq = fn (int $base): int => max(1, (int) round($base * $r));
        $fs = fn (int $base): int => max(6, (int) round($base * $r));

        $t = fn (array $e): array => $e + [
            'type' => 'text', 'size' => $fs(12), 'weight' => 600, 'color' => $dark,
            'align' => 'left', 'font' => 'jost', 'italic' => false, 'leading' => 1.25, 'upper' => false,
        ];

        // Logo UK large (suit le réglage des Réglages) ; chemin relatif pour le PDF.
        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        // Variante AVEC PHOTO du même badge : le nom de l'événement REMONTE, une photo
        // s'intercale entre l'événement et le nom, et nom/rôle/thème DESCENDENT pour lui
        // faire la place. La photo (élément `photo`) se remplit avec `recipient.photo`
        // (profil), sinon les initiales. Le concepteur (`co-photo`) décrit la MÊME maquette.
        $eventY = $fy($withPhoto ? 0.1928 : 0.2669);
        $nameY = $fy($withPhoto ? 0.4852 : 0.4153);
        $roleY = $fy($withPhoto ? 0.5636 : 0.4936);
        $themeY = $fy($withPhoto ? 0.6250 : 0.5742);
        $themeH = $fy($withPhoto ? 0.1250 : 0.1420);

        $photoSize = $sq(84);
        $photo = $withPhoto
            ? [['type' => 'photo', 'x' => (int) round(($w - $photoSize) / 2), 'y' => $fy(0.2775), 'w' => $photoSize, 'h' => $photoSize]]
            : [];

        $icon = $sq(20);
        $qr = $sq(65);

        return [
            'width' => $w, 'height' => $h, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'bg_image' => '/img/badges/badge-default-bg.png',
            'erase_default_rule' => true,
            'elements' => array_merge([
                // ⚠️ Les deux filets verts (gauche cadre→UK, droite Parakou→cadre) sont
                // CUITS DANS LE CADRE de l'image de fond (badge-default-bg.png), dans la
                // même matière que le contour : ils y sont donc TOUJOURS soudés, sans la
                // moindre fente - un filet posé en élément par-dessus laissait un jour à
                // gauche dans le concepteur. Ils s'étirent avec le cadre (100% 100%).
                // Ne PAS les repasser en `rect` : c'est précisément ce qui ne « collait » pas.
                // Logos OFFICIELS, les mêmes que les attestations : UK large (réglages) et
                // le logo large de Parakou, à la MÊME hauteur, côte à côte et centrés.
                ['type' => 'image', 'x' => $fx(0.0871), 'y' => $fy(0.0953), 'w' => $fx(0.3664), 'h' => $fy(0.0720), 'src' => (string) $ukLogo],
                ['type' => 'image', 'x' => $fx(0.5135), 'y' => $fy(0.0953), 'w' => $fx(0.3994), 'h' => $fy(0.0720), 'src' => '/img/attestations/logo-parakou.png'],
                // Nom de l'événement (série + édition).
                $t(['x' => $fx(0.0601), 'y' => $eventY, 'w' => $fx(0.8799), 'h' => $fy(0.0953), 'text' => '[edition.name]', 'size' => $fs(13), 'weight' => 700, 'align' => 'center']),
            ], $photo, [
                // Bénéficiaire (grand, bleu) + fonction/rôle juste en dessous.
                $t(['x' => $fx(0.0511), 'y' => $nameY, 'w' => $fx(0.8979), 'h' => $fy(0.0720), 'text' => '[recipient.name]', 'size' => $fs(18), 'weight' => 800, 'align' => 'center', 'color' => '#253c98']),
                $t(['x' => $fx(0.0511), 'y' => $roleY, 'w' => $fx(0.8979), 'h' => $fy(0.0424), 'text' => '[recipient.role]', 'size' => $fs(13), 'weight' => 700, 'align' => 'center']),
                // Thème du colloque, en capitales.
                $t(['x' => $fx(0.1021), 'y' => $themeY, 'w' => $fx(0.7988), 'h' => $themeH, 'text' => '[edition.theme]', 'size' => $fs(9), 'weight' => 700, 'align' => 'center', 'color' => '#111111', 'leading' => 1.3, 'upper' => true]),
                ['type' => 'rect', 'shape' => 'line-h', 'x' => $fx(0.2733), 'y' => $fy(0.7436), 'w' => $fx(0.4565), 'h' => max(2, $fy(0.0064)), 'color' => $dark, 'radius' => 2],
                // Les pictogrammes sont des éléments indépendants, comme les logos.
                ['type' => 'image', 'x' => $fx(0.1772), 'y' => $fy(0.7754), 'w' => $icon, 'h' => $icon, 'src' => '/img/badges/icon-location.svg', 'color' => $dark],
                ['type' => 'image', 'x' => $fx(0.1772), 'y' => $fy(0.8432), 'w' => $icon, 'h' => $icon, 'src' => '/img/badges/icon-calendar.svg', 'color' => $dark],
                // Lieu et dates.
                $t(['x' => $fx(0.2462), 'y' => $fy(0.7818), 'w' => $fx(0.4775), 'h' => $fy(0.0403), 'text' => '[edition.location]', 'size' => $fs(10)]),
                $t(['x' => $fx(0.2462), 'y' => $fy(0.8496), 'w' => $fx(0.4775), 'h' => $fy(0.0403), 'text' => '[edition.dates]', 'size' => $fs(10)]),
                ['type' => 'qr', 'x' => $fx(0.7417), 'y' => $fy(0.7818), 'size' => $qr, 'value' => '{verify_url}'],
            ]),
        ];
    }

    /**
     * Maquette CARTE (CR80, 85,6 × 54 mm) : le badge est en PAYSAGE, donc la pile
     * verticale du portrait n'y tient pas.
     *
     * ⚠️ **Présentation CENTRÉE** : les logos coiffent la carte, puis l'événement,
     * le NOM et la fonction sont centrés sous un filet court ; le lieu et les dates
     * occupent le pied à gauche, le QR le coin bas-droit. C'est la mise en page
     * retenue après comparaison de trois rendus - une version alignée à gauche
     * laissait un vide à droite, et le filet y passait pour un soulignement de la
     * fonction.
     *
     * ⚠️ **Le thème du colloque n'y figure pas**, et c'est voulu : une phrase de
     * cent caractères descendrait sous 2 mm de haut sur une carte de crédit - on
     * afficherait quelque chose d'illisible pour faire nombre. La carte porte ce
     * qu'on lit à un mètre : l'événement, le NOM, la fonction, le lieu, les dates
     * et le QR.
     *
     * Proportions décrites sur 324 × 204 px, composées pour le format demandé.
     *
     * @return array<string, mixed>
     */
    private static function badgeCarte(int $w, int $h, bool $withPhoto): array
    {
        $dark = '#2e3440';

        $fx = fn (float $f): int => (int) round($f * $w);
        $fy = fn (float $f): int => (int) round($f * $h);

        // ⚠️ Un seul rapport pour les carrés et les corps de texte : suivre les deux
        // axes séparément ovaliserait le QR et étirerait le texte.
        $r = min($w / 324, $h / 204);
        $sq = fn (int $base): int => max(1, (int) round($base * $r));
        $fs = fn (int $base): int => max(6, (int) round($base * $r));

        $t = fn (array $e): array => $e + [
            'type' => 'text', 'size' => $fs(8), 'weight' => 600, 'color' => $dark,
            'align' => 'left', 'font' => 'jost', 'italic' => false, 'leading' => 1.2, 'upper' => false,
        ];

        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        // AVEC PHOTO : elle prend la gauche, et le bloc centré se resserre sur la
        // place qui reste - le centrer sur la carte entière le ferait chevaucher.
        $colX = $fx($withPhoto ? 0.2963 : 0.0432);
        $colW = $fx($withPhoto ? 0.6605 : 0.9136);

        // Le filet se centre SOUS le bloc, quelle que soit sa largeur.
        $sepW = $fx($withPhoto ? 0.2778 : 0.3086);
        $sepX = $colX + (int) round(($colW - $sepW) / 2);

        $photo = $withPhoto
            ? [['type' => 'photo', 'x' => $fx(0.0432), 'y' => $fy(0.2549), 'w' => $sq(70), 'h' => $sq(70)]]
            : [];

        $icon = $sq(13);

        return [
            'width' => $w, 'height' => $h, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'bg_image' => '/img/badges/badge-default-bg.png',
            'erase_default_rule' => true,
            'elements' => array_merge([
                // Logos OFFICIELS, à la même hauteur, en tête de carte.
                ['type' => 'image', 'x' => $fx(0.0432), 'y' => $fy(0.0588), 'w' => $fx(0.2346), 'h' => $fy(0.0980), 'src' => (string) $ukLogo],
                ['type' => 'image', 'x' => $fx(0.2963), 'y' => $fy(0.0588), 'w' => $fx(0.2531), 'h' => $fy(0.0980), 'src' => '/img/attestations/logo-parakou.png'],
            ], $photo, [
                // Identité, centrée.
                $t(['x' => $colX, 'y' => $fy(0.1961), 'w' => $colW, 'h' => $fy(0.0637), 'text' => '[edition.name]', 'size' => $fs(8), 'weight' => 700, 'align' => 'center']),
                $t(['x' => $colX, 'y' => $fy(0.2941), 'w' => $colW, 'h' => $fy(0.1569), 'text' => '[recipient.name]', 'size' => $fs(21), 'weight' => 800, 'color' => '#253c98', 'align' => 'center']),
                $t(['x' => $colX, 'y' => $fy(0.4608), 'w' => $colW, 'h' => $fy(0.0784), 'text' => '[recipient.role]', 'size' => $fs(11), 'weight' => 700, 'align' => 'center']),
                ['type' => 'rect', 'shape' => 'line-h', 'x' => $sepX, 'y' => $fy(0.5784), 'w' => $sepW, 'h' => max(2, $fy(0.0098)), 'color' => $dark, 'radius' => 1],
                // Pied : lieu et dates à gauche, chacun derrière son pictogramme.
                ['type' => 'image', 'x' => $fx(0.0432), 'y' => $fy(0.6667), 'w' => $icon, 'h' => $icon, 'src' => '/img/badges/icon-location.svg', 'color' => $dark],
                ['type' => 'image', 'x' => $fx(0.0432), 'y' => $fy(0.7941), 'w' => $icon, 'h' => $icon, 'src' => '/img/badges/icon-calendar.svg', 'color' => $dark],
                $t(['x' => $fx(0.1019), 'y' => $fy(0.6716), 'w' => $fx(0.5864), 'h' => $fy(0.0588), 'text' => '[edition.location]', 'size' => $fs(8)]),
                $t(['x' => $fx(0.1019), 'y' => $fy(0.7990), 'w' => $fx(0.5864), 'h' => $fy(0.0588), 'text' => '[edition.dates]', 'size' => $fs(8)]),
                // QR dans le coin bas-droit, hors du pied.
                ['type' => 'qr', 'x' => $fx(0.7469), 'y' => $fy(0.6275), 'size' => $sq(62), 'value' => '{verify_url}'],
            ]),
        ];
    }

    protected static function defaultAttestation(): array
    {
        return [
            'width' => 900, 'height' => 636, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'elements' => [
                ['type' => 'rect', 'x' => 0, 'y' => 0, 'w' => 900, 'h' => 14, 'color' => '#223ea4'],
                ['type' => 'text', 'x' => 60, 'y' => 60, 'w' => 780, 'h' => 26, 'text' => '{org}', 'size' => 16, 'weight' => 800, 'color' => '#223ea4', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 150, 'w' => 780, 'h' => 50, 'text' => 'Attestation de {type}', 'size' => 34, 'weight' => 900, 'color' => '#1a2332', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 240, 'w' => 780, 'h' => 24, 'text' => 'Décernée à', 'size' => 16, 'weight' => 500, 'color' => '#5a6577', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 274, 'w' => 780, 'h' => 44, 'text' => '{name}', 'size' => 28, 'weight' => 800, 'color' => '#223ea4', 'align' => 'center'],
                ['type' => 'text', 'x' => 120, 'y' => 350, 'w' => 660, 'h' => 60, 'text' => 'pour sa {type} à {event}, {location}.', 'size' => 16, 'weight' => 500, 'color' => '#1a2332', 'align' => 'center'],
                ['type' => 'text', 'x' => 60, 'y' => 560, 'w' => 400, 'h' => 22, 'text' => 'Délivrée le {date}', 'size' => 13, 'weight' => 500, 'color' => '#5a6577', 'align' => 'left'],
                ['type' => 'qr', 'x' => 760, 'y' => 500, 'size' => 90, 'value' => '{verify_url}'],
            ],
        ];
    }

    /**
     * Attestation de COMMUNICATION (modèle officiel UK x Université de Parakou).
     *
     * Le cadre décoratif (motifs kente, hélice ADN) est porté par l'image de fond ;
     * les logos et le texte sont des éléments éditables. Jetons de la palette du
     * concepteur (`[espace.cle]`). Titre en **Times** (dompdf : Times natif ; écran :
     * Times New Roman), corps en **Lato** (hébergée, cf. DocumentFonts) : rendu
     * identique écran/PDF, fidèle à la typographie du modèle officiel.
     */
    protected static function attestationCommunication(): array
    {
        $dark = '#1a2332';

        // Fabrique un élément texte : les valeurs par défaut (Lato, corps) sont
        // surchargées par les clés passées (p. ex. une autre police ou graisse).
        $t = fn (array $e): array => $e + [
            'type' => 'text', 'size' => 14, 'weight' => 500, 'color' => $dark,
            'align' => 'left', 'font' => 'lato', 'italic' => false, 'leading' => 1.3,
        ];

        // Logos en éléments image (pas cuits dans le fond) : l'UK suit le logo large
        // des Réglages, Parakou est l'asset livré. Chemins relatifs (indépendants du
        // domaine) : le rendu PDF les résout sur le disque, l'écran sur l'origine.
        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        return [
            'width' => 900, 'height' => 636, 'bg' => '#ffffff', 'accent' => '#223ea4',
            'bg_image' => '/img/attestations/attestation-communication-bg.png',
            'elements' => [
                ['type' => 'image', 'x' => 135, 'y' => 26, 'w' => 230, 'h' => 60, 'src' => (string) $ukLogo],
                ['type' => 'image', 'x' => 620, 'y' => 26, 'w' => 260, 'h' => 60, 'src' => '/img/attestations/logo-parakou.png'],
                // Titre en Times 28 px : largeur et hauteur de capitales identiques au
                // modèle (mesurées à ~508 px de large sur la maquette d'origine).
                $t(['x' => 120, 'y' => 104, 'w' => 660, 'h' => 40, 'text' => 'ATTESTATION DE COMMUNICATION', 'size' => 28, 'weight' => 700, 'align' => 'center', 'font' => 'times']),
                $t(['x' => 120, 'y' => 152, 'w' => 660, 'h' => 24, 'text' => 'Décernée à', 'size' => 15, 'align' => 'center', 'color' => '#2b3340']),
                $t(['x' => 120, 'y' => 178, 'w' => 660, 'h' => 40, 'text' => '[recipient.civility] [recipient.name]', 'size' => 23, 'weight' => 800, 'align' => 'center']),
                $t(['x' => 125, 'y' => 226, 'w' => 640, 'h' => 58, 'text' => 'pour avoir participé et communiqué au [edition.name], [edition.dates], à [edition.location].', 'size' => 14, 'leading' => 1.4]),
                $t(['x' => 125, 'y' => 286, 'w' => 195, 'h' => 22, 'text' => 'Thème du colloque :', 'weight' => 700]),
                $t(['x' => 330, 'y' => 286, 'w' => 436, 'h' => 40, 'text' => '[edition.theme]', 'weight' => 700]),
                $t(['x' => 125, 'y' => 350, 'w' => 200, 'h' => 22, 'text' => 'Titre de la communication :', 'weight' => 700]),
                $t(['x' => 330, 'y' => 350, 'w' => 436, 'h' => 40, 'text' => '[communication.title]', 'weight' => 700]),
                $t(['x' => 125, 'y' => 414, 'w' => 200, 'h' => 22, 'text' => 'Type de communication :', 'weight' => 700]),
                $t(['x' => 330, 'y' => 414, 'w' => 220, 'h' => 22, 'text' => '[communication.type]', 'weight' => 700]),
                $t(['x' => 125, 'y' => 460, 'w' => 648, 'h' => 24, 'text' => 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', 'size' => 14]),
                ['type' => 'qr', 'x' => 120, 'y' => 500, 'size' => 76, 'value' => '{verify_url}'],
                $t(['x' => 490, 'y' => 490, 'w' => 350, 'h' => 22, 'text' => 'Fait à Kara, le [certificate.issued_on]']),
                $t(['x' => 490, 'y' => 514, 'w' => 350, 'h' => 24, 'text' => 'Le [signatory.role]', 'size' => 15, 'weight' => 700]),
                $t(['x' => 490, 'y' => 560, 'w' => 350, 'h' => 28, 'text' => '[signatory.name]', 'size' => 17, 'weight' => 700]),
                $t(['x' => 30, 'y' => 588, 'w' => 430, 'h' => 20, 'text' => 'Identifiant de l’attestation : [certificate.uuid]', 'size' => 11, 'weight' => 700]),
            ],
        ];
    }

    /**
     * Attestation de PARTICIPATION (modèle officiel UK x Université de Parakou).
     * Doit rester identique au modèle du concepteur (`loadTemplate('participation')`).
     */
    protected static function attestationParticipation(): array
    {
        return self::officialAttestation(
            'ATTESTATION DE PARTICIPATION',
            'pour avoir participé au [edition.name], [edition.dates], à [edition.location].'
        );
    }

    /**
     * Attestation de MODÉRATION (modèle officiel UK x Université de Parakou).
     * Même maquette que la participation, titre et corps mis à part. Doit rester
     * identique au modèle du concepteur (`loadTemplate('moderation')`).
     */
    protected static function attestationModeration(): array
    {
        return self::officialAttestation(
            'ATTESTATION DE MODÉRATION',
            'pour avoir modéré une session lors du [edition.name], [edition.dates], à [edition.location].'
        );
    }

    /**
     * Attestation de RAPPORTEUR (modèle officiel UK x Université de Parakou).
     * Même maquette que la participation, titre et corps mis à part. Doit rester
     * identique au modèle du concepteur (`loadTemplate('rapporteur')`).
     */
    protected static function attestationRapporteur(): array
    {
        return self::officialAttestation(
            'ATTESTATION DE RAPPORTEUR',
            'pour avoir servi en tant que rapporteur lors du [edition.name], [edition.dates], à [edition.location].'
        );
    }

    /**
     * Attestation d'INSTRUCTION DE RÉSUMÉS (évaluateurs / évaluateurs de résumés).
     * Même maquette officielle que modération / rapporteur, titre et corps mis à part.
     * Titre « ATTESTATION D'INSTRUCTION DE RÉSUMÉS » : Times 28 px ≈ 618 px, tient dans
     * la boîte. Doit rester identique au modèle du concepteur
     * (`loadTemplate('instruction_resumes')`).
     */
    protected static function attestationInstructionResumes(): array
    {
        return self::officialAttestation(
            'ATTESTATION D’INSTRUCTION DE RÉSUMÉS',
            'pour avoir contribué à l’évaluation, la sélection et l’annotation des résumés scientifiques soumis au [edition.name], [edition.dates], à [edition.location].'
        );
    }

    /**
     * Attestation de FORMATION (modèle officiel UK x Parakou - design « moderne » à
     * coins colorés, distinct du cadre kente des autres). Rédaction à la première
     * personne (« Je soussigné, … »), sujet de la formation + thème du colloque.
     *
     * ⚠️ Deux signatures possibles : le responsable de l'événement (bloc à droite,
     * texte : rôle + nom) ET, EN PLUS, jusqu'à deux FORMATEURS (élément `signatures`
     * à gauche, images figées à l'émission depuis `formation_trainers.signature_path`,
     * cf. AttestationVars['signatures']). Sans signature de formateur téléversée,
     * l'élément est vide et seul le responsable signe.
     *
     * Doit rester identique au modèle du concepteur (`loadTemplate('formation')`).
     */
    protected static function attestationFormation(): array
    {
        $dark = '#1a2332';

        $t = fn (array $e): array => $e + [
            'type' => 'text', 'size' => 14, 'weight' => 500, 'color' => $dark,
            'align' => 'left', 'font' => 'lato', 'italic' => false, 'leading' => 1.3,
        ];

        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        return [
            'width' => 900, 'height' => 636, 'bg' => '#f0f0f0', 'accent' => '#223ea4',
            'bg_image' => '/img/attestations/attestation-formation-bg.png',
            'elements' => [
                // Deux logos à la MÊME hauteur (comme la maquette officielle) : la
                // largeur suit le format propre de chaque logo, pas l'inverse - sinon
                // l'un paraît plus grand que l'autre.
                ['type' => 'image', 'x' => 56, 'y' => 44, 'w' => 204, 'h' => 56, 'src' => (string) $ukLogo],
                ['type' => 'image', 'x' => 616, 'y' => 44, 'w' => 228, 'h' => 56, 'src' => '/img/attestations/logo-parakou.png'],
                // Titre plus grand que les autres modèles (mesuré ≈ 576 px à Times 38 px).
                $t(['x' => 80, 'y' => 118, 'w' => 740, 'h' => 52, 'text' => 'ATTESTATION DE FORMATION', 'size' => 38, 'weight' => 700, 'align' => 'center', 'font' => 'times']),
                $t(['x' => 70, 'y' => 186, 'w' => 760, 'h' => 28, 'text' => 'Je soussigné, [signatory.name], [signatory.role], atteste que', 'size' => 14]),
                $t(['x' => 70, 'y' => 220, 'w' => 760, 'h' => 40, 'text' => '[recipient.civility] [recipient.name]', 'size' => 22, 'weight' => 800, 'align' => 'center']),
                $t(['x' => 70, 'y' => 272, 'w' => 765, 'h' => 120, 'text' => 'a suivi avec diligence la formation sur [formation.title], tenue en marge des travaux du [edition.name], qui s’est tenu [edition.dates] à [edition.location], sur le thème : « [edition.theme] ».', 'size' => 14, 'leading' => 1.45]),
                $t(['x' => 70, 'y' => 378, 'w' => 760, 'h' => 24, 'text' => 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', 'size' => 14]),
                // Signatures des formateurs (jusqu'à deux) - à gauche, en vis-à-vis du responsable.
                ['type' => 'signatures', 'x' => 70, 'y' => 470, 'w' => 350, 'h' => 92, 'size' => 11, 'names' => true],
                // QR de vérification dans le coin bas-droit (comme les autres modèles le
                // posent dans un coin), hors du bloc signataire du milieu.
                ['type' => 'qr', 'x' => 782, 'y' => 548, 'size' => 76, 'value' => '{verify_url}'],
                // Responsable de l'événement - à droite (texte, comme la maquette). L'écart
                // fonction -> nom suit la maquette officielle (~80 px).
                $t(['x' => 500, 'y' => 438, 'w' => 360, 'h' => 22, 'text' => 'Fait à Kara, le [certificate.issued_on]', 'size' => 13]),
                $t(['x' => 500, 'y' => 470, 'w' => 360, 'h' => 24, 'text' => 'Le [signatory.role],', 'size' => 14, 'weight' => 700]),
                $t(['x' => 500, 'y' => 550, 'w' => 360, 'h' => 28, 'text' => '[signatory.name]', 'size' => 16, 'weight' => 700]),
                $t(['x' => 40, 'y' => 582, 'w' => 520, 'h' => 20, 'text' => 'Référence de l’attestation : [certificate.uuid]', 'size' => 11, 'weight' => 700]),
            ],
        ];
    }

    /**
     * Attestation d'ANIMATION DE FORMATION (délivrée au FORMATEUR). Même cadre que
     * l'attestation de formation (coins colorés, carte grise) mais rédaction
     * « Décernée à … » comme la maquette officielle : pas de signatures de formateur
     * (le formateur EST le bénéficiaire ; seul le responsable signe). Doit rester
     * identique au modèle du concepteur (`loadTemplate('formation_animation')`).
     */
    protected static function attestationFormationAnimation(): array
    {
        $dark = '#1a2332';

        $t = fn (array $e): array => $e + [
            'type' => 'text', 'size' => 14, 'weight' => 500, 'color' => $dark,
            'align' => 'left', 'font' => 'lato', 'italic' => false, 'leading' => 1.3,
        ];

        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        return [
            'width' => 900, 'height' => 636, 'bg' => '#f0f0f0', 'accent' => '#223ea4',
            'bg_image' => '/img/attestations/attestation-formation-bg.png',
            'elements' => [
                ['type' => 'image', 'x' => 56, 'y' => 44, 'w' => 204, 'h' => 56, 'src' => (string) $ukLogo],
                ['type' => 'image', 'x' => 616, 'y' => 44, 'w' => 228, 'h' => 56, 'src' => '/img/attestations/logo-parakou.png'],
                // Titre long (« … D'ANIMATION DE FORMATION ») : Times 30 px ≈ 673 px, tient
                // sur une ligne dans la boîte de 820 px.
                $t(['x' => 40, 'y' => 112, 'w' => 820, 'h' => 40, 'text' => 'ATTESTATION D’ANIMATION DE FORMATION', 'size' => 30, 'weight' => 700, 'align' => 'center', 'font' => 'times']),
                $t(['x' => 120, 'y' => 160, 'w' => 660, 'h' => 24, 'text' => 'Décernée à', 'size' => 15, 'align' => 'center', 'color' => '#2b3340']),
                $t(['x' => 120, 'y' => 186, 'w' => 660, 'h' => 40, 'text' => '[recipient.civility] [recipient.name]', 'size' => 22, 'weight' => 800, 'align' => 'center']),
                $t(['x' => 80, 'y' => 232, 'w' => 740, 'h' => 120, 'text' => 'pour avoir animé une formation en marge des travaux du [edition.name], qui s’est tenu [edition.dates] à [edition.location], sur le thème : « [edition.theme] ».', 'size' => 14, 'leading' => 1.45]),
                $t(['x' => 80, 'y' => 358, 'w' => 230, 'h' => 22, 'text' => 'Thème de la formation :', 'weight' => 700]),
                $t(['x' => 318, 'y' => 358, 'w' => 500, 'h' => 44, 'text' => '[formation.title]', 'weight' => 700]),
                $t(['x' => 80, 'y' => 414, 'w' => 740, 'h' => 24, 'text' => 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', 'size' => 14]),
                ['type' => 'qr', 'x' => 80, 'y' => 486, 'size' => 76, 'value' => '{verify_url}'],
                // Responsable de l'événement (seul signataire) - à droite. Écart fonction
                // -> nom ~80 px, comme les autres modèles.
                $t(['x' => 500, 'y' => 452, 'w' => 360, 'h' => 22, 'text' => 'Fait à Kara, le [certificate.issued_on]', 'size' => 13]),
                $t(['x' => 500, 'y' => 484, 'w' => 360, 'h' => 24, 'text' => 'Le [signatory.role],', 'size' => 14, 'weight' => 700]),
                $t(['x' => 500, 'y' => 564, 'w' => 360, 'h' => 28, 'text' => '[signatory.name]', 'size' => 16, 'weight' => 700]),
                $t(['x' => 40, 'y' => 596, 'w' => 520, 'h' => 20, 'text' => 'Référence de l’attestation : [certificate.uuid]', 'size' => 11, 'weight' => 700]),
            ],
        ];
    }

    /**
     * Attestation du COMITÉ D'ORGANISATION (membres du comité, par opposition à
     * l'attestation d'appui). Même maquette officielle ; titre « ATTESTATION DU COMITÉ
     * D'ORGANISATION » (tient à 28 px). Doit rester identique au modèle du concepteur
     * (`loadTemplate('comite_organisation')`).
     */
    protected static function attestationComiteOrganisation(): array
    {
        return self::officialAttestation(
            'ATTESTATION DU COMITÉ D’ORGANISATION',
            'pour avoir été membre du comité d’organisation du [edition.name], [edition.dates], à [edition.location].'
        );
    }

    /**
     * Attestation de PARTICIPATION À L'ORGANISATION (soutien au comité d'organisation).
     * Même maquette officielle ; titre plus long, ramené à Times 24 px pour tenir sur
     * une ligne. Doit rester identique au modèle du concepteur (`loadTemplate('organisation')`).
     */
    protected static function attestationOrganisation(): array
    {
        return self::officialAttestation(
            'ATTESTATION DE PARTICIPATION À L’ORGANISATION',
            'pour avoir travaillé en appui au comité d’organisation, lors du [edition.name], [edition.dates], à [edition.location].',
            24
        );
    }

    /**
     * Maquette officielle UK x Parakou commune (participation, modération…) : même
     * cadre décoratif (image de fond partagée), mêmes logos en éléments image, même
     * typographie (Times pour le titre, Lato pour le corps), QR de vérification et
     * bloc signataire. Seuls le TITRE et le CORPS varient d'un type à l'autre.
     *
     * ⚠️ Toute retouche de géométrie doit être reportée dans le modèle jumeau du
     * concepteur (`officialTemplate()` dans `template-designer.js`).
     */
    protected static function officialAttestation(string $title, string $body, int $titleSize = 28): array
    {
        $dark = '#1a2332';

        $t = fn (array $e): array => $e + [
            'type' => 'text', 'size' => 14, 'weight' => 500, 'color' => $dark,
            'align' => 'left', 'font' => 'lato', 'italic' => false, 'leading' => 1.3,
        ];

        $ukLogo = parse_url(Branding::wide(), PHP_URL_PATH) ?: Branding::wide();

        return [
            'width' => 900, 'height' => 636, 'bg' => '#ffffff', 'accent' => '#223ea4',
            // Cadre identique à l'attestation de communication (même design UK/Parakou).
            'bg_image' => '/img/attestations/attestation-communication-bg.png',
            'elements' => [
                ['type' => 'image', 'x' => 135, 'y' => 26, 'w' => 230, 'h' => 60, 'src' => (string) $ukLogo],
                ['type' => 'image', 'x' => 620, 'y' => 26, 'w' => 260, 'h' => 60, 'src' => '/img/attestations/logo-parakou.png'],
                // ⚠️ $titleSize est mesuré pour tenir sur UNE ligne dans la boîte de 660 px
                // (titre long « … À L'ORGANISATION » : Times 24 px ≈ 642 px, cf. mesure).
                $t(['x' => 120, 'y' => 104, 'w' => 660, 'h' => 40, 'text' => $title, 'size' => $titleSize, 'weight' => 700, 'align' => 'center', 'font' => 'times']),
                $t(['x' => 120, 'y' => 152, 'w' => 660, 'h' => 24, 'text' => 'Décernée à', 'size' => 15, 'align' => 'center', 'color' => '#2b3340']),
                $t(['x' => 120, 'y' => 178, 'w' => 660, 'h' => 40, 'text' => '[recipient.civility] [recipient.name]', 'size' => 23, 'weight' => 800, 'align' => 'center']),
                $t(['x' => 125, 'y' => 234, 'w' => 648, 'h' => 58, 'text' => $body, 'size' => 14, 'leading' => 1.4]),
                $t(['x' => 125, 'y' => 316, 'w' => 195, 'h' => 22, 'text' => 'Thème du colloque :', 'weight' => 700]),
                $t(['x' => 330, 'y' => 316, 'w' => 436, 'h' => 44, 'text' => '[edition.theme]', 'weight' => 700]),
                $t(['x' => 125, 'y' => 408, 'w' => 648, 'h' => 24, 'text' => 'En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.', 'size' => 14]),
                ['type' => 'qr', 'x' => 120, 'y' => 492, 'size' => 76, 'value' => '{verify_url}'],
                $t(['x' => 490, 'y' => 452, 'w' => 350, 'h' => 22, 'text' => 'Fait à Kara, le [certificate.issued_on]']),
                $t(['x' => 490, 'y' => 476, 'w' => 350, 'h' => 24, 'text' => 'Le [signatory.role]', 'size' => 15, 'weight' => 700]),
                $t(['x' => 490, 'y' => 556, 'w' => 350, 'h' => 28, 'text' => '[signatory.name]', 'size' => 17, 'weight' => 700]),
                $t(['x' => 30, 'y' => 592, 'w' => 430, 'h' => 20, 'text' => 'Identifiant de l’attestation : [certificate.uuid]', 'size' => 11, 'weight' => 700]),
            ],
        ];
    }

    protected static function clampInt(mixed $v, int $min, int $max, int $default): int
    {
        if (! is_numeric($v)) {
            return $default;
        }

        return (int) max($min, min($max, (int) $v));
    }

    protected static function color(mixed $v, string $default): string
    {
        $v = is_string($v) ? trim($v) : '';

        return preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v) ? $v : $default;
    }
}
