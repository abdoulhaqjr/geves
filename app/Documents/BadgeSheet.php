<?php

namespace App\Documents;

/**
 * Tuilage d'une planche de badges sur A4 : combien de badges par page, de quelle
 * taille, et la mise en page réduite à cette taille.
 *
 * ⚠️ Pourquoi des gouttières imposent de RÉDUIRE le badge : un A6 (105×148 mm) est
 * EXACTEMENT le quart d'une A4 (210×297 mm), donc 2×2 A6 remplissent la feuille bord
 * à bord - zéro place pour un espace. On réserve marge + gouttière, puis on met le
 * badge à l'échelle de ce qui reste. Ne pas revenir à gap:0 / margin:0 : le modèle
 * .docx de référence veut l'espace.
 *
 * ⚠️ La réduction se fait en RECALCULANT la mise en page (scaleLayout), jamais par un
 * `transform: scale()` CSS : dompdf ne l'applique pas de façon fiable, et c'est lui
 * qui produit le PDF imprimé.
 */
final class BadgeSheet
{
    /**
     * Badges par PDF.
     *
     * ⚠️ Un plafond, et il est volontaire : chaque badge est composé élément par
     * élément, et une édition de 200 inscrits épuisait la mémoire de dompdf **au
     * milieu** du rendu (mesuré : 128 Mo dépassés, page blanche au bout). Le lot
     * borné échoue vite ou réussit vite. Même raison que
     * `AttestationExportController::LOT`.
     */
    public const LOT = 48; // 12 feuilles A4 à 4 badges

    /** A4 portrait à 96 dpi (les px CSS valent 1/96 pouce à l'impression). */
    private const PAGE_W = 793.7;   // 210 mm

    private const PAGE_H = 1122.52; // 297 mm

    private const MARGIN_MM = 6;    // marge autour de la feuille

    private const GUTTER_MM = 5;    // espace entre deux badges

    /**
     * Plan de tuilage pour un badge de w×h px.
     *
     * @return array{cols:int, rows:int, perPage:int, scale:float, cellW:int, cellH:int, gutter:int, marginMm:int, sheetW:int}
     */
    public static function plan(int $w, int $h): array
    {
        $w = max(1, $w);
        $h = max(1, $h);

        $mm = 96 / 25.4;
        $margin = self::MARGIN_MM * $mm;
        $gutter = self::GUTTER_MM * $mm;

        // Tolérance 1,2 % : l'A6 fait 105,03 mm, soit 2×210,06 mm, à peine au-delà
        // de l'A4 ; sans elle il ne passerait qu'à une colonne. Le rétrécissement
        // rend ensuite la place aux gouttières.
        $tol = 1.012;
        $cols = max(1, (int) floor(self::PAGE_W * $tol / $w));
        $rows = max(1, (int) floor(self::PAGE_H * $tol / $h));

        $availW = self::PAGE_W - 2 * $margin;
        $availH = self::PAGE_H - 2 * $margin;
        $scaleW = ($availW - ($cols - 1) * $gutter) / ($cols * $w);
        $scaleH = ($availH - ($rows - 1) * $gutter) / ($rows * $h);
        $scale = round(min($scaleW, $scaleH, 1.0) * 0.998, 4); // marge anti-rognage

        $cellW = max(1, (int) floor($w * $scale));
        $cellH = max(1, (int) floor($h * $scale));

        return [
            'cols' => $cols,
            'rows' => $rows,
            'perPage' => $cols * $rows,
            'scale' => $scale,
            'cellW' => $cellW,
            'cellH' => $cellH,
            'gutter' => (int) round($gutter),
            'marginMm' => self::MARGIN_MM,
            'sheetW' => $cols * $cellW + ($cols - 1) * (int) round($gutter),
        ];
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
