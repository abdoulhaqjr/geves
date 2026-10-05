<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Review;

/**
 * Grille d'évaluation structurée des relectures : liste de critères notés (0–5),
 * dont on dérive une note globale /20. La grille est configurable par événement
 * (réglage `review_criteria`), avec un jeu de critères académiques par défaut.
 *
 * Un critère : ['key' => string, 'label' => string].
 */
class ReviewGrid
{
    /** Note maximale par critère (échelle 0–SCALE). */
    public const SCALE = 5;

    /** Relecteurs visés par soumission, à défaut de réglage d'événement. */
    public const DEFAULT_TARGET = 2;

    /** Grille académique par défaut (originalité, rigueur, clarté, pertinence, contribution). */
    public const DEFAULTS = [
        ['key' => 'originality', 'label' => 'Originalité et intérêt'],
        ['key' => 'rigor', 'label' => 'Rigueur scientifique / méthodologique'],
        ['key' => 'clarity', 'label' => 'Clarté et qualité rédactionnelle'],
        ['key' => 'relevance', 'label' => 'Pertinence par rapport à la thématique'],
        ['key' => 'contribution', 'label' => 'Contribution et portée'],
    ];

    /**
     * Nombre de relecteurs visé par soumission (1-5, défaut 2). Règle de
     * l'événement, lue par l'attribution automatique.
     *
     * ⚠️ Comme `review_criteria` et `review_due_days`, ce réglage vit à la
     * RACINE de `edition.settings` - pas dans le sac « doc » d'EventSettings.
     */
    public static function target(?Event $event): int
    {
        $n = (int) ($event?->settings['review_target'] ?? 0);

        return $n >= 1 && $n <= 5 ? $n : self::DEFAULT_TARGET;
    }

    /**
     * Plafond de relectures par relecteur (0 = illimité). Règle de l'événement.
     */
    public static function maxLoad(?Event $event): int
    {
        $n = (int) ($event?->settings['review_max_load'] ?? 0);

        return $n >= 0 && $n <= 50 ? $n : 0;
    }

    /**
     * Délai d'évaluation de l'événement, en jours (borné 1-180). Sert d'échéance à
     * l'assignation (cf. App\Actions\AssignReviewer) ; à défaut, la durée
     * standard Review::DEFAULT_DUE_DAYS.
     *
     * ⚠️ Comme `review_criteria`, ce réglage vit à la RACINE de
     * `edition.settings` - pas dans le sac « doc » d'EventSettings, réservé
     * aux documents (attestations, badges, livret).
     */
    public static function dueDays(?Event $event): int
    {
        $days = (int) ($event?->settings['review_due_days'] ?? 0);

        return $days >= 1 && $days <= 180 ? $days : Review::DEFAULT_DUE_DAYS;
    }

    /**
     * Grille effective d'un événement (réglage propre à l'événement sinon défaut).
     *
     * @return list<array{key:string, label:string}>
     */
    public static function criteria(?Event $event): array
    {
        $custom = $event?->settings['review_criteria'] ?? null;

        return self::normalize(is_array($custom) ? $custom : null);
    }

    /**
     * Nettoie/valide une liste de critères ; retourne la grille par défaut si vide.
     *
     * @param  array<int|string, mixed>|null  $raw
     * @return list<array{key:string, label:string}>
     */
    public static function normalize(?array $raw): array
    {
        if ($raw === null) {
            return self::DEFAULTS;
        }

        $out = [];
        $seen = [];
        foreach ($raw as $row) {
            $label = trim((string) (is_array($row) ? ($row['label'] ?? '') : $row));
            if ($label === '') {
                continue;
            }
            $key = trim((string) (is_array($row) ? ($row['key'] ?? '') : ''));
            if ($key === '') {
                $key = self::slug($label);
            }
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = ['key' => $key, 'label' => $label];
        }

        return $out === [] ? self::DEFAULTS : $out;
    }

    /**
     * Note globale /20 dérivée des notes par critère (moyenne à poids égaux),
     * ou null si aucune note exploitable.
     *
     * @param  array<string, mixed>  $scores
     * @param  list<array{key:string, label:string}>  $criteria
     */
    public static function overall(array $scores, array $criteria): ?float
    {
        $values = [];
        foreach ($criteria as $c) {
            $v = $scores[$c['key']] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            $values[] = max(0.0, min((float) self::SCALE, (float) $v));
        }

        if ($values === []) {
            return null;
        }

        $mean = array_sum($values) / count($values);

        return round($mean / self::SCALE * 20, 2);
    }

    /**
     * Ne conserve que les notes des critères de la grille, bornées à [0, SCALE].
     *
     * @param  array<string, mixed>  $scores
     * @param  list<array{key:string, label:string}>  $criteria
     * @return array<string, int>
     */
    public static function sanitizeScores(array $scores, array $criteria): array
    {
        $out = [];
        foreach ($criteria as $c) {
            $v = $scores[$c['key']] ?? null;
            if ($v === null || $v === '') {
                continue;
            }
            $out[$c['key']] = (int) max(0, min(self::SCALE, (int) $v));
        }

        return $out;
    }

    private static function slug(string $label): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(self::ascii($label)));

        return trim((string) $slug, '_');
    }

    private static function ascii(string $value): string
    {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $t === false ? $value : $t;
    }
}
