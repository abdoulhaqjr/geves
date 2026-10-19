<?php

namespace App\Documents;

/**
 * Disposition d'une planche de badges sur A4.
 *
 * ⚠️⚠️ **UN BADGE S'IMPRIME À SA TAILLE RÉELLE.** Ce sont des dimensions de SORTIE :
 * le badge entre dans une pochette (modèle 303, 304, 106, CR80, A6 porte-badge), donc
 * on ne le rétrécit pas pour en faire tenir davantage. Notre travail est de les
 * DISPOSER sur la feuille en tenant compte de la zone non imprimable des imprimantes.
 * Le seul rétrécissement admis est un filet de sécurité : un format plus grand que la
 * feuille, qui sortirait rogné.
 *
 * ⚠️ La réduction, quand elle a lieu, RECALCULE la mise en page (`scaleLayout`) : elle
 * n'applique jamais un `transform: scale()` CSS, que dompdf ne rend pas fidèlement.
 */
final class BadgeSheet
{
    /**
     * Badges par PDF.
     *
     * ⚠️ Un plafond, et il est volontaire : chaque badge est composé élément par
     * élément, et une édition de 200 inscrits épuisait la mémoire de dompdf **au
     * milieu** du rendu (128 Mo dépassés, page blanche au bout). Le lot borné
     * échoue vite ou réussit vite. Même raison que
     * `AttestationExportController::LOT`.
     */
    public const LOT = 48;

    /**
     * Marge d'impression minimale.
     *
     * ⚠️ C'est la **zone non imprimable** des imprimantes courantes : en dessous,
     * le bord du badge est rogné à l'impression. 5 mm couvre le parc habituel.
     */
    public const MIN_MARGIN_MM = 5;

    /** A4 portrait à 96 dpi (les px CSS valent 1/96 pouce à l'impression). */
    private const PAGE_W = 793.7;   // 210 mm

    private const PAGE_H = 1122.52; // 297 mm

    /** Espace de coupe souhaité entre deux badges. */
    private const GUTTER_MM = 5;

    /**
     * Formats de porte-badges du marché, pour nommer ce qu'on imprime.
     * Clé « largeur×hauteur » en mm.
     */
    private const MODELES = [
        '88x125' => 'Modèle 303',
        '100x135' => 'Modèle 304',
        '65x95' => 'Modèle 106',
        '108x155' => 'A6 porte-badge',
        '86x54' => 'CR80',
        '105x148' => 'A6 papier',
        '148x105' => 'A6 papier paysage',
        '74x105' => 'A7 papier',
        '90x55' => 'Carte 90 × 55',
    ];

    /**
     * Plan de disposition pour un badge de w×h px (96 dpi).
     *
     * @return array{cols:int, rows:int, perPage:int, scale:float, cellW:int, cellH:int,
     *               gutterX:int, gutterY:int, originLeft:int, originTop:int,
     *               sheetW:int, sheetH:int, pageW:int, pageH:int, minMarginPx:int,
     *               trueSize:bool, label:string}
     */
    public static function plan(int $w, int $h): array
    {
        $w = max(1, $w);
        $h = max(1, $h);

        $mm = 96 / 25.4;
        $minMargin = self::MIN_MARGIN_MM * $mm;
        $prefGutter = self::GUTTER_MM * $mm;

        $usableW = self::PAGE_W - 2 * $minMargin;
        $usableH = self::PAGE_H - 2 * $minMargin;

        // ⚠️ Taille réelle par défaut. On ne réduit QUE si le badge ne tient pas
        // une seule fois sur la feuille - sinon il sortirait rogné.
        $scale = 1.0;
        if ($w > $usableW || $h > $usableH) {
            $scale = round(min($usableW / $w, $usableH / $h) * 0.998, 4);
        }

        $cellW = max(1, (int) floor($w * $scale));
        $cellH = max(1, (int) floor($h * $scale));

        [$cols, $gutterX] = self::axe($usableW, $cellW, $prefGutter);
        [$rows, $gutterY] = self::axe($usableH, $cellH, $prefGutter);

        $sheetW = $cols * $cellW + ($cols - 1) * $gutterX;
        $sheetH = $rows * $cellH + ($rows - 1) * $gutterY;

        return [
            'cols' => $cols,
            'rows' => $rows,
            'perPage' => $cols * $rows,
            'scale' => $scale,
            'cellW' => $cellW,
            'cellH' => $cellH,
            'gutterX' => $gutterX,
            'gutterY' => $gutterY,
            'sheetW' => $sheetW,
            'sheetH' => $sheetH,
            // La grille est CENTRÉE : le reliquat se partage entre les deux bords
            // plutôt que de tout laisser d'un côté.
            'originLeft' => max(0, (int) round((self::PAGE_W - $sheetW) / 2)),
            'originTop' => max(0, (int) round((self::PAGE_H - $sheetH) / 2)),
            'pageW' => (int) round(self::PAGE_W),
            'pageH' => (int) round(self::PAGE_H),
            'minMarginPx' => (int) floor($minMargin),
            'trueSize' => $scale >= 1.0,
            'label' => self::formatLabel($w, $h),
        ];
    }

    /**
     * Combien de badges tiennent sur un axe, et l'espace de coupe retenu.
     *
     * ⚠️ On essaie d'abord avec l'espace souhaité, puis SANS : deux badges peuvent
     * partager leur trait de coupe, et c'est souvent ce qui fait tenir une colonne
     * de plus (un badge de 100 mm : 2×100 = 200 mm, soit la feuille moins ses
     * marges, à un dixième de millimètre près).
     *
     * @return array{0:int, 1:int}
     */
    private static function axe(float $usable, int $cell, float $prefGutter): array
    {
        $tol = 1.0; // 1 px ≈ 0,26 mm : l'arrondi ne doit pas coûter une colonne

        $large = max(1, (int) floor(($usable + $prefGutter + $tol) / ($cell + $prefGutter)));
        $serre = max(1, (int) floor(($usable + $tol) / $cell));

        if ($serre > $large) {
            // Une rangée de plus, en resserrant : on répartit ce qui reste.
            return [$serre, max(0, (int) floor(($usable - $serre * $cell) / max(1, $serre - 1)))];
        }

        return [$large, (int) floor($prefGutter)];
    }

    /** Nom lisible d'un format : « Modèle 303 - 88 × 125 mm ». */
    public static function formatLabel(int $w, int $h): string
    {
        $mmW = (int) round($w / 96 * 25.4);
        $mmH = (int) round($h / 96 * 25.4);
        $taille = $mmW.' × '.$mmH.' mm';

        $modele = self::MODELES[$mmW.'x'.$mmH] ?? null;

        return $modele ? $modele.' - '.$taille : $taille;
    }

    /**
     * Rend la MÊME maquette, dessinée à `$cellW × $cellH` : toute la géométrie est
     * multipliée par l'échelle (position, taille, corps du texte, interlettrage,
     * rayon, filet). ⚠️ `leading` est un RAPPORT, pas une longueur : il ne se met
     * pas à l'échelle, sinon l'interligne doublerait.
     *
     * @param  array<string, mixed>  $layout
     * @return array<string, mixed>
     */
    public static function scaleLayout(array $layout, float $scale, int $cellW, int $cellH): array
    {
        $px = static fn (mixed $v, int $min = 0): int => max($min, (int) round(((float) $v) * $scale));

        $elements = [];
        foreach ((array) ($layout['elements'] ?? []) as $el) {
            $el = (array) $el;
            $el['x'] = $px($el['x'] ?? 0);
            $el['y'] = $px($el['y'] ?? 0);

            foreach (['w', 'h'] as $key) {
                if (isset($el[$key])) {
                    $el[$key] = $px($el[$key], 1);
                }
            }
            // `size` porte le côté du QR, le corps d'un texte et celui des signatures.
            if (isset($el['size'])) {
                $el['size'] = $px($el['size'], 1);
            }
            foreach (['radius', 'border'] as $key) {
                if (isset($el[$key])) {
                    $el[$key] = $px($el[$key]);
                }
            }
            if (isset($el['spacing'])) {
                $el['spacing'] = round(((float) $el['spacing']) * $scale, 3);
            }

            $elements[] = $el;
        }

        $layout['elements'] = $elements;
        $layout['width'] = $cellW;
        $layout['height'] = $cellH;

        return $layout;
    }
}
