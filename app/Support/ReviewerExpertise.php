<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Review;
use Illuminate\Support\Collection;

/**
 * Relecteurs d'un événement avec leur expertise (axes cochés) et leur charge.
 *
 * Source UNIQUE : la matrice vivait dans le formulaire d'événement et une copie en
 * lecture seule sur la page d'attribution automatique, chacune avec sa propre
 * requête. Elle a désormais son onglet dans le module Soumissions
 * (« Relecteurs & expertise »), au plus près du moteur qui la consomme.
 */
class ReviewerExpertise
{
    /**
     * Clé de comparaison d'un axe thématique.
     *
     * ⚠️ L'expertise d'un relecteur stocke une COPIE du libellé de l'axe. Or ce
     * libellé est du texte libre : un tiret cadratin collé depuis Word, une
     * double espace ou une majuscule suffisaient à faire échouer l'égalité
     * stricte - sans aucun signal. Résultat observé en base : tous les axes
     * stockés en « Atelier 1 — … » alors que l'événement dit « Atelier 1 - … »,
     * donc AUCUN appariement par axe ne fonctionnait et l'attribution proposait
     * systématiquement « hors axe ».
     */
    public static function axisKey(string $axis): string
    {
        $s = mb_strtolower(trim($axis));
        // Tirets typographiques (cadratin, demi-cadratin, insécable…) -> tiret simple.
        $s = str_replace(["\u{2014}", "\u{2013}", "\u{2012}", "\u{2011}", "\u{2010}"], '-', $s);

        return trim(preg_replace('/\s+/', ' ', $s) ?? '');
    }

    /**
     * L'un des axes déclarés par le relecteur correspond-il à celui-ci ?
     *
     * @param  array<int, string>  $tracks
     */
    public static function covers(array $tracks, string $axis): bool
    {
        if ($axis === '') {
            return false;
        }
        $key = self::axisKey($axis);

        foreach ($tracks as $t) {
            if (self::axisKey((string) $t) === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, array{id:int, name:string, institution:string, tracks:array<int, string>, load:int}>
     */
    public static function forEdition(?Event $event): Collection
    {
        if (! $event) {
            return collect();
        }

        $reviewers = Participant::where('event_id', $event->id)
            ->where('role', 'reviewer')->orderBy('last_name')->get();

        // ⚠️ Charge = relectures ACTIVES seulement : un refus ou une annulation
        // libère la place, il ne doit plus peser sur le relecteur.
        $load = Review::query()
            ->whereIn('reviewer_id', $reviewers->pluck('id'))
            ->whereIn('status', Review::ACTIVE_STATUSES)
            ->whereHas('submission', fn ($q) => $q->where('event_id', $event->id))
            ->selectRaw('reviewer_id, count(*) as c')->groupBy('reviewer_id')->pluck('c', 'reviewer_id');

        return $reviewers->map(fn (Participant $r) => [
            'id' => (int) $r->id,
            'name' => $r->full_name,
            'institution' => (string) ($r->institution ?? ''),
            'tracks' => collect($r->meta['review_tracks'] ?? [])
                ->filter()->map(fn ($t): string => trim((string) $t))->values()->all(),
            'load' => (int) ($load[$r->id] ?? 0),
        ])->values();
    }

    /**
     * Axes thématiques de l'événement (réglages), pour cocher l'expertise.
     *
     * @return list<string>
     */
    public static function axes(?Event $event): array
    {
        return collect($event?->settings['tracks'] ?? [])
            ->map(fn ($t): string => trim((string) $t))
            ->filter()->unique()->values()->all();
    }
}
