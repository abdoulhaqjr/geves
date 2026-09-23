<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Qui peut rejoindre la visio d'une session : tout le monde (public) ou les
 * seuls participants inscrits de l'édition. Gating plus fin (payés seulement…)
 * à décider avec le mockup - cf. EventSession::meetingRoleFor().
 */
enum MeetingVisibility: string
{
    use HasLabels;

    case Public = 'public';
    case Participants = 'participants';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public (tout le monde)',
            self::Participants => 'Participants inscrits',
        };
    }

    /** Variante de pastille (.ukc-pill--*). */
    public function pill(): string
    {
        return match ($this) {
            self::Public => 'ok',
            self::Participants => 'info',
        };
    }
}
