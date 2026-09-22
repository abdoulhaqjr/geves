<?php

namespace App\Support;

/**
 * Compare une valeur de période courante à la période précédente et produit
 * une tendance exploitable par le widget stat (flèche ▲/▼, variation %).
 * Aucune donnée inventée : les contrôleurs fournissent les deux mesures réelles.
 */
class Trend
{
    /**
     * @return array{current: int, previous: int, delta: int, dir: string, pct: int|null}
     *                                                                                    dir ∈ {up, down, flat}. pct null = pas de base de comparaison (nouveauté).
     */
    public static function of(int $current, int $previous): array
    {
        $delta = $current - $previous;
        $dir = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        $pct = $previous > 0 ? (int) round(abs($delta) / $previous * 100) : null;

        return [
            'current' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'dir' => $dir,
            'pct' => $pct,
        ];
    }
}
