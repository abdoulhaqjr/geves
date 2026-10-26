<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Str;

/**
 * Résolution des frais d'inscription d'un événement (grille tarifaire par profil,
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
     * ⚠️⚠️ **UNE CATÉGORIE « HORS GRADE » EST DÉCLARÉE, PAS DÉDUITE**
     * (`selectable`). Une ONG, une entreprise, une association ne se reconnaissent
     * à aucun grade académique : il n'existe aucune valeur de
     * {@see ParticipantTypes} qui les désigne, donc aucune déduction possible.
     * C'est le participant qui l'annonce, et c'est la SEULE exception à la règle
     * « le tarif ne se choisit pas, il se déduit du grade ».
     *
     * ⚠️ Une catégorie hors grade ne porte donc **ni grades ni défaut**, et on
     * le force ici plutôt que de s'en remettre au formulaire : les deux se
     * contrediraient un jour, et la cascade automatique appliquerait « ONG » à
     * quelqu'un qui ne l'a jamais déclaré.
     *
     * ⚠️ **La clé est STABLE, le libellé ne l'est pas.** C'est elle que le choix
     * du participant enregistre : rapprochée sur le libellé, renommer
     * « Entreprises » en « Entreprises & industries » ferait silencieusement
     * retomber tous ceux qui l'avaient choisie sur leur grade - donc sur un autre
     * montant. (Même leçon que les axes d'expertise, comparés par clé.)
     *
     * @return list<array{key: string, label: string, amount: float, grades: list<string>, default: bool, selectable: bool}>
     */
    public static function normalize(mixed $raw): array
    {
        $vues = [];

        return collect(is_array($raw) ? $raw : [])
            ->filter(fn ($fee) => is_array($fee) && trim((string) ($fee['label'] ?? '')) !== '')
            ->map(function ($fee) use (&$vues) {
                $selectable = (bool) ($fee['selectable'] ?? false);

                // Clé enregistrée si elle existe ; sinon dérivée du libellé, pour
                // que les grilles saisies avant ce champ restent exploitables.
                $cle = trim((string) ($fee['key'] ?? '')) ?: Str::slug((string) $fee['label']);
                $cle = $cle !== '' ? $cle : 'categorie';
                if (isset($vues[$cle])) {
                    $cle .= '-'.(++$vues[$cle]);
                } else {
                    $vues[$cle] = 1;
                }

                return [
                    'key' => $cle,
                    'label' => (string) $fee['label'],
                    'amount' => (float) ($fee['amount'] ?? 0),
                    'grades' => $selectable ? [] : collect(is_array($fee['grades'] ?? null) ? $fee['grades'] : [])
                        ->map(fn ($g) => trim((string) $g))->filter()->unique()->values()->all(),
                    'default' => ! $selectable && (bool) ($fee['default'] ?? false),
                    'selectable' => $selectable,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Les catégories que le participant peut DÉCLARER (ONG, entreprise…).
     *
     * @return list<array{key: string, label: string, amount: float, grades: list<string>, default: bool, selectable: bool}>
     */
    public static function selectable(Event $event): array
    {
        return array_values(array_filter(self::grid($event), fn ($f) => $f['selectable']));
    }

    /**
     * Le montant dû par CE participant - le seul point de résolution.
     *
     * ⚠️⚠️ **LA CATÉGORIE DÉCLARÉE PASSE DEVANT LE GRADE.** C'est tout son
     * objet : le grade d'un délégué d'ONG, s'il en a un, ne dit rien de ce
     * qu'il doit. Sans cette priorité, la catégorie déclarée serait une case
     * sans effet.
     *
     * ⚠️ **Une catégorie disparue de la grille ne retient rien** : on retombe
     * sur le grade, plutôt que de facturer un montant que la grille n'annonce
     * plus. Le choix reste enregistré - l'organisation peut la rétablir.
     */
    public static function forParticipant(?Participant $participant): ?float
    {
        if (! $participant || ! $participant->event) {
            return null;
        }

        $declaree = self::declaredCategory($participant);

        return $declaree['amount'] ?? self::resolveAmount($participant->event, $participant->title);
    }

    /** Le libellé du tarif appliqué à ce participant (déclaré, sinon déduit du grade). */
    public static function labelForParticipant(?Participant $participant): ?string
    {
        if (! $participant || ! $participant->event) {
            return null;
        }

        if ($declaree = self::declaredCategory($participant)) {
            return $declaree['label'];
        }

        $montant = self::resolveAmount($participant->event, $participant->title);
        if ($montant === null) {
            return null;
        }

        foreach (self::grid($participant->event) as $fee) {
            if (! $fee['selectable'] && (float) $fee['amount'] === (float) $montant) {
                return $fee['label'];
            }
        }

        return null;
    }

    /**
     * La catégorie hors grade DÉCLARÉE par ce participant, si elle existe encore.
     *
     * @return array{key: string, label: string, amount: float, grades: list<string>, default: bool, selectable: bool}|null
     */
    public static function declaredCategory(?Participant $participant): ?array
    {
        // ⚠️ Branche explicite, pas de `?->` sur `event` : Larastan type la
        // relation BelongsTo comme non-nulle et y voit un nullsafe inutile.
        if (! $participant || ! $participant->event) {
            return null;
        }

        $cle = $participant->feeCategoryKey();
        if ($cle === null) {
            return null;
        }

        foreach (self::selectable($participant->event) as $fee) {
            if ($fee['key'] === $cle) {
                return $fee;
            }
        }

        return null;
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
                // ⚠️ Une catégorie hors grade ne s'attrape JAMAIS par déduction,
                // même si son libellé tombe sur un grade : elle se déclare.
                if (! $fee['selectable'] && trim(mb_strtolower($fee['label'])) === $grade) {
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

    /**
     * Montant le plus élevé de la grille (repli quand le grade est inconnu).
     *
     * ⚠️⚠️ **LES CATÉGORIES HORS GRADE EN SONT EXCLUES.** Ce repli frappe qui
     * n'a rien déclaré et dont le grade n'est couvert par rien : lui appliquer
     * le tarif « Entreprises » parce qu'il se trouve être le plus cher de la
     * grille facturerait un tarif d'entreprise à un doctorant. Le repli ne
     * retient que ce qui pouvait lui être appliqué automatiquement.
     */
    public static function highestAmount(Event $event): ?float
    {
        $amounts = array_map(
            fn ($f) => (float) $f['amount'],
            array_filter(self::grid($event), fn ($f) => ! $f['selectable']),
        );

        return $amounts ? max($amounts) : null;
    }
}
