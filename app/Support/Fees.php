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
        return self::normalize($edition->settings['fees'] ?? null);
    }

    /**
     * Normalise une valeur brute settings.fees en une liste bien formée. Seules
     * les entrées qui sont des tableaux avec un libellé non vide sont conservées
     * (forme garantie ['label' => string, 'amount' => float]) ; toute entrée
     * malformée (valeur scalaire, libellé vide, format legacy « label => montant »)
     * est ignorée. Ainsi, une donnée fees corrompue ne casse plus le rendu.
     *
     * @return list<array{label: string, amount: float}>
     */
    public static function normalize(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [])
            ->filter(fn ($fee) => is_array($fee) && trim((string) ($fee['label'] ?? '')) !== '')
            ->map(fn ($fee) => [
                'label' => (string) $fee['label'],
                'amount' => (float) ($fee['amount'] ?? 0),
            ])
            ->values()
            ->all();
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
