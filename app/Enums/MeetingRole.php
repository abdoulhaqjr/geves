<?php

namespace App\Enums;

/**
 * Rôle d'un utilisateur dans la visio d'une session, calculé (non stocké) :
 * - Moderator   : anime la salle (staff, ou modérateur assigné de la session) ;
 * - Presenter   : communicant dont la communication est programmée dans la session ;
 * - Attendee    : assiste (participant inscrit, ou public si visio publique) ;
 * - None        : accès refusé.
 */
enum MeetingRole: string
{
    case Moderator = 'moderator';
    case Presenter = 'presenter';
    case Attendee = 'attendee';
    case None = 'none';

    /** Droits d'animation (créer/gérer la salle, couper les micros…). */
    public function isModerator(): bool
    {
        return $this === self::Moderator;
    }

    /** Peut rejoindre la salle (tout sauf None). */
    public function canJoin(): bool
    {
        return $this !== self::None;
    }
}
