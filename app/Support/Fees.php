<?php

namespace App\Support;

use App\Models\Event;

/**
 * Résolution des frais d'inscription d'une événement (grille tarifaire par profil,
 * stockée dans settings.fees).
 */
class Fees
{
    /** Grille tarifaire : liste de ['label' => string, 'amount' => float]. */
    public static function grid(Event $event): array
    {
        return self::normalize($event->settings['fees'] ?? null);
    }

    /**
     * Normalise une valeur brute settings.fees en une liste bien formée. Seules
     * les entrées qui sont des tableaux avec un libellé non vide sont conservées
     * (forme garantie ['label' => string, 'amount' => float]) ; toute entrée
     * malformée (valeur scalaire, libellé vide, format legacy « label => montant »)
     * est ignorée. Ainsi, une donnée fees corrompue ne casse plus le rendu.
     *
     * Chaque entrée porte en plus les **grades couverts** (`grades`) et un
     * éventuel drapeau **catégorie par défaut** (`default`) : c'est ce
     * rattachement explicite qui permet de retrouver le tarif d'un participant,
     * le libellé étant rédigé librement (« Doctorants et assimilés » ne
     * correspond à aucun grade).
     *
     * @return list<array{label: string, amount: float, grades: list<string>, default: bool}>
     */
    public static function normalize(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [])
            ->filter(fn ($fee) => is_array($fee) && trim((string) ($fee['label'] ?? '')) !== '')
            ->map(fn ($fee) => [
                'label' => (string) $fee['label'],
                'amount' => (float) ($fee['amount'] ?? 0),
                'grades' => collect(is_array($fee['grades'] ?? null) ? $fee['grades'] : [])
                    ->map(fn ($g) => trim((string) $g))->filter()->unique()->values()->all(),
                'default' => (bool) ($fee['default'] ?? false),
            ])
            ->values()
            ->all();
    }

    /**
     * Montant correspondant à un grade/profil de participant, dans l'ordre :
     *   1. la catégorie qui **déclare couvrir ce grade** (rattachement explicite) ;
     *   2. à défaut, une catégorie dont le **libellé est exactement le grade**
     *      (compatibilité avec les grilles saisies avant le rattachement) ;
     *   3. à défaut, la catégorie marquée **par défaut** (« Autres participants »).
     *
     * Retourne null si rien ne correspond : l'appelant décide alors quoi faire
     * (montant à préciser par l'organisation).
     */
    public static function resolveAmount(Event $event, ?string $grade): ?float
    {
        $grade = trim(mb_strtolower((string) $grade));
        $grid = self::grid($event);

        if ($grade !== '') {
            foreach ($grid as $fee) {
                foreach ($fee['grades'] as $covered) {
                    if (mb_strtolower($covered) === $grade) {
                        return $fee['amount'];
                    }
                }
            }

            foreach ($grid as $fee) {
                if (trim(mb_strtolower($fee['label'])) === $grade) {
                    return $fee['amount'];
                }
            }
        }

        foreach ($grid as $fee) {
            if ($fee['default']) {
                return $fee['amount'];
            }
        }

        return null;
    }

    /** Montant le plus élevé de la grille (repli quand le grade est inconnu). */
    public static function highestAmount(Event $event): ?float
    {
        $amounts = array_map(fn ($f) => (float) ($f['amount'] ?? 0), self::grid($event));

        return $amounts ? max($amounts) : null;
    }
}
