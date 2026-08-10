<?php

namespace App\Support;

use App\Models\Edition;

/**
 * Résolution des frais d'inscription d'une édition (grille tarifaire par profil,
 * stockée dans settings.fees).
 */
class Fees
{
    /** Grille tarifaire : liste de ['label' => string, 'amount' => float]. */
    public static function grid(Edition $edition): array
    {
        return $edition->settings['fees'] ?? [];
    }

    /**
     * Montant correspondant à un grade/profil (rapprochement insensible à la
     * casse). Retourne null si aucun tarif ne correspond (l'admin fixe alors le
     * montant manuellement).
     */
    public static function resolveAmount(Edition $edition, ?string $grade): ?float
    {
        $grade = trim(mb_strtolower((string) $grade));
        if ($grade === '') {
            return null;
        }

        foreach (self::grid($edition) as $fee) {
            if (trim(mb_strtolower($fee['label'] ?? '')) === $grade) {
                return (float) ($fee['amount'] ?? 0);
            }
        }

        return null;
    }

    /** Montant le plus élevé de la grille (repli quand le grade est inconnu). */
    public static function highestAmount(Edition $edition): ?float
    {
        $amounts = array_map(fn ($f) => (float) ($f['amount'] ?? 0), self::grid($edition));

        return $amounts ? max($amounts) : null;
    }
}
