<?php

namespace App\Support;

use App\Models\ReviewerBid;

/**
 * Moteur d'attribution automatique des relecteurs (déterministe, sans I/O donc
 * testable en isolation). À partir des soumissions à couvrir, des relecteurs
 * candidats (expertise + charge courante) et d'une carte des conflits d'intérêts,
 * il propose des affectations en :
 *   - excluant tout conflit d'intérêt ÉLEVÉ (règle dure) ;
 *   - privilégiant l'adéquation d'axe (expertise du relecteur ↔ axe de la soumission) ;
 *   - équilibrant la charge (un relecteur peu chargé passe devant) sans dépasser un
 *     plafond par relecteur ;
 *   - pénalisant (sans interdire) un conflit d'intérêt moyen/faible.
 * La décision finale reste humaine : ceci alimente un aperçu que l'admin valide.
 */
class ReviewerAssignmentPlanner
{
    private const SCORE_AXIS_MATCH = 100;

    private const SCORE_GENERALIST = 20;

    private const SCORE_VOLUNTEER = 60;

    /** Valeurs de candidature (miroir de {@see ReviewerBid}, sans couplage). */
    private const BID_INTERESTED = 'interested';

    private const BID_UNAVAILABLE = 'unavailable';

    private const PENALTY_COI_MEDIUM = 40;

    private const PENALTY_COI_LOW = 5;

    private const PENALTY_PER_LOAD = 10;

    /**
     * @param  list<array{id:int, track:string, needed:int, exclude?:list<int>}>  $submissions
     * @param  list<array{id:int, tracks:list<string>, load:int}>  $reviewers
     * @param  array<int, array<int, string|null>>  $coi  [submissionId][reviewerId] => niveau ConflictOfInterest ou null
     * @param  int  $maxLoad  charge maximale par relecteur (≤ 0 = illimitée)
     * @param  array<int, array<int, array{interest?:string, conflict?:bool}>>  $bids  [submissionId][reviewerId] => candidature du relecteur
     * @return list<array{submission_id:int, reviewer_id:int, reason:string, coi:string|null, axis_match:bool}>
     */
    public function plan(array $submissions, array $reviewers, array $coi, int $maxLoad, array $bids = []): array
    {
        $cap = $maxLoad > 0 ? $maxLoad : PHP_INT_MAX;

        // Charge projetée par relecteur (part de la charge courante, s'incrémente).
        $load = [];
        $tracksById = [];
        foreach ($reviewers as $r) {
            $load[$r['id']] = $r['load'];
            $tracksById[$r['id']] = $r['tracks'];
        }

        $proposals = [];

        foreach ($submissions as $sub) {
            $needed = max(0, $sub['needed']);
            if ($needed === 0) {
                continue;
            }
            $exclude = array_flip($sub['exclude'] ?? []);

            // Classe les candidats éligibles pour cette soumission.
            $ranked = [];
            foreach ($reviewers as $r) {
                $rid = $r['id'];
                if (isset($exclude[$rid])) {
                    continue;
                }
                if ($load[$rid] >= $cap) {
                    continue;
                }
                $level = $coi[$sub['id']][$rid] ?? null;
                if ($level === ConflictOfInterest::HIGH) {
                    continue; // conflit d'intérêt élevé : jamais proposé
                }

                $bid = $bids[$sub['id']][$rid] ?? null;
                if (($bid['conflict'] ?? false) === true) {
                    continue; // conflit auto-déclaré par le relecteur
                }
                $interest = $bid['interest'] ?? null;
                if ($interest === self::BID_UNAVAILABLE) {
                    continue; // le relecteur s'est déclaré indisponible
                }
                $volunteer = $interest === self::BID_INTERESTED;

                $axisMatch = $sub['track'] !== '' && in_array($sub['track'], $tracksById[$rid], true);
                $score = 0;
                if ($axisMatch) {
                    $score += self::SCORE_AXIS_MATCH;
                } elseif ($tracksById[$rid] === []) {
                    $score += self::SCORE_GENERALIST; // généraliste (aucune expertise déclarée)
                }
                if ($volunteer) {
                    $score += self::SCORE_VOLUNTEER; // candidat volontaire
                }
                if ($level === ConflictOfInterest::MEDIUM) {
                    $score -= self::PENALTY_COI_MEDIUM;
                } elseif ($level === ConflictOfInterest::LOW) {
                    $score -= self::PENALTY_COI_LOW;
                }
                $score -= $load[$rid] * self::PENALTY_PER_LOAD;

                $ranked[] = ['id' => $rid, 'score' => $score, 'load' => $load[$rid], 'axis_match' => $axisMatch, 'coi' => $level, 'volunteer' => $volunteer];
            }

            // Tri déterministe : score desc, puis charge asc, puis id asc.
            usort($ranked, function ($a, $b) {
                return [$b['score'], -$a['load'], -$a['id']] <=> [$a['score'], -$b['load'], -$b['id']];
            });

            foreach (array_slice($ranked, 0, $needed) as $pick) {
                $proposals[] = [
                    'submission_id' => $sub['id'],
                    'reviewer_id' => $pick['id'],
                    'reason' => $this->reason($pick['axis_match'], $tracksById[$pick['id']] === [], $pick['volunteer']),
                    'coi' => $pick['coi'],
                    'axis_match' => $pick['axis_match'],
                ];
                $load[$pick['id']]++;
            }
        }

        return $proposals;
    }

    private function reason(bool $axisMatch, bool $generalist, bool $volunteer = false): string
    {
        $base = $axisMatch
            ? 'Expertise sur l’axe'
            : ($generalist ? 'Relecteur généraliste' : 'Hors axe (équilibrage)');

        return $volunteer ? $base.' · volontaire' : $base;
    }
}
