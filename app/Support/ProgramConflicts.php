<?php

namespace App\Support;

use App\Models\Edition;
use App\Models\EventSession;
use Illuminate\Support\Carbon;

/**
 * Règle métier d'exclusivité des sessions PLÉNIÈRES dans le programme.
 *
 * Une session plénière est EXCLUSIVE : aucune autre activité ne peut se tenir en
 * même temps qu'elle. En revanche, les ateliers peuvent se dérouler EN PARALLÈLE
 * entre eux (sessions simultanées). Autrement dit, un chevauchement horaire est
 * interdit dès que l'un des deux éléments est une plénière, et autorisé entre deux
 * ateliers (ou entre créneaux d'ateliers). Utilisé pour bloquer la sauvegarde
 * d'une session/d'un créneau en conflit (planning admin + affectation auto).
 */
class ProgramConflicts
{
    /**
     * Première session de l'édition qui rend le placement INTERDIT sur l'intervalle
     * [$start, $end[, ou null s'il n'y a pas de conflit.
     *
     * @param  bool  $isPlenary  L'élément placé est-il lui-même une plénière ?
     *                           - plénière : en conflit avec N'IMPORTE quelle autre session ;
     *                           - atelier / créneau : en conflit uniquement avec une plénière.
     * @param  int|null  $ignoreSessionId  Session à exclure (ré-enregistrement d'elle-même).
     */
    public static function blockingSession(
        Edition $edition,
        ?Carbon $start,
        ?Carbon $end,
        bool $isPlenary,
        ?int $ignoreSessionId = null,
    ): ?EventSession {
        if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
            return null; // horaire incomplet ou invalide : rien à vérifier
        }

        return $edition->sessions()
            ->whereNotNull('starts_at')->whereNotNull('ends_at')
            ->when($ignoreSessionId !== null, fn ($q) => $q->where('id', '!=', $ignoreSessionId))
            // Un atelier/créneau n'est en conflit qu'avec une plénière ; une
            // plénière, elle, est en conflit avec toute session.
            ->when(! $isPlenary, fn ($q) => $q->where('type', 'plenary'))
            // Chevauchement strict : début < fin de l'autre ET fin > début de l'autre
            // (deux créneaux qui se touchent aux bornes ne se chevauchent pas).
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->orderBy('starts_at')
            ->first();
    }

    /** Message d'erreur prêt à l'emploi pour un conflit avec une plénière. */
    public static function message(EventSession $conflict): string
    {
        $when = trim(($conflict->starts_at?->format('H:i') ?? '').' - '.($conflict->ends_at?->format('H:i') ?? ''), ' -');
        $where = $when !== '' ? ' ('.$when.')' : '';

        return $conflict->type === 'plenary'
            ? 'Créneau en conflit avec la session plénière « '.$conflict->label.' »'.$where
                .'. Une plénière est exclusive : aucune autre activité ne peut se tenir en même temps.'
            : 'Une session plénière doit être exclusive : elle ne peut pas chevaucher « '.$conflict->label.' »'.$where.'.';
    }
}
