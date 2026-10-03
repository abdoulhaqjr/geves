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

    /**
     * Rôle tel que la SALLE le nomme (lecteur LiveKit, `resources/js/meeting.js`).
     *
     * ⚠️ La salle ne connaît que trois mots : `moderator`, `presenter`,
     * `audience`. Ce vocabulaire est écrit ici **une seule fois** parce qu'il
     * voyage par deux chemins qui doivent dire la même chose : la config du
     * lecteur (pour soi-même) et la **métadonnée du jeton** (pour les autres).
     * Les voir diverger, c'est un participant qui se croit présentateur et que
     * personne d'autre ne voit comme tel.
     */
    public function roomRole(): string
    {
        return match ($this) {
            self::Moderator => 'moderator',
            self::Presenter => 'presenter',
            default => 'audience',
        };
    }

    /** Peut rejoindre la salle (tout sauf None). */
    public function canJoin(): bool
    {
        return $this !== self::None;
    }
}
