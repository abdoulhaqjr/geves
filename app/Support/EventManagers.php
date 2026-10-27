<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * « Qui pilote cet événement ? » - source unique.
 *
 * Les gestionnaires et co-gestionnaires d'un événement (`event_user.role`),
 * avec repli sur les administrateurs : un événement sans gestionnaire ne doit
 * laisser aucune remontée sans destinataire. Un périmètre `null` (pas
 * d'événement) vise directement les administrateurs. Partagé par les remontées
 * ciblées du back-office (réclamations, resoumissions) pour qu'elles ne
 * divergent jamais sur la définition.
 */
class EventManagers
{
    /** Rôles d'équipe qui pilotent un événement. */
    public const ROLES = ['gerant', 'co_gerant'];

    /**
     * @return Collection<int, User>
     */
    public static function forEvent(?int $eventId): Collection
    {
        // ⚠️ `administrateurs()` et non la seule colonne `access_level` : un
        // compte qui tient ses droits du rôle hérité ou de `manage_colloques`
        // est administrateur pour le modèle, et serait pourtant écarté d'une
        // requête sur `access_level` seul.
        if ($eventId === null) {
            return self::activeAdministrators();
        }

        $ids = DB::table('event_user')
            ->where('event_id', $eventId)
            ->whereIn('role', self::ROLES)
            ->pluck('user_id')->unique();

        $managers = $ids->isEmpty()
            ? new Collection
            : User::query()->whereIn('id', $ids)->where('is_active', true)->get();

        return $managers->isNotEmpty() ? $managers : self::activeAdministrators();
    }

    /** Un compte désactivé ne peut pas traiter une alerte reçue. */
    private static function activeAdministrators(): Collection
    {
        return User::query()->administrateurs()->where('is_active', true)->get();
    }
}
