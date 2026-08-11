<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * État de la visio d'une session : aucune, programmée, en direct, terminée.
 * Piloté par le staff au fil de l'événement.
 */
enum MeetingStatus: string
{
    use HasLabels;

    case None = 'none';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucune visio',
            self::Scheduled => 'Visio programmée',
            self::Live => 'En direct',
            self::Ended => 'Visio terminée',
        };
    }

    /** Variante de pastille (.ukc-pill--*). */
    public function pill(): string
    {
        return match ($this) {
            self::None => 'muted',
            self::Scheduled => 'info',
            self::Live => 'err', // rouge « en direct »
            self::Ended => 'muted',
        };
    }
}
