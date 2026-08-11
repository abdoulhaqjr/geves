<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * État de diffusion en direct d'une session : aucune diffusion, programmée,
 * en direct, ou terminée. Piloté par le staff au fil de l'événement.
 */
enum StreamStatus: string
{
    use HasLabels;

    case None = 'none';
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucune diffusion',
            self::Scheduled => 'Diffusion programmée',
            self::Live => 'En direct',
            self::Ended => 'Diffusion terminée',
        };
    }

    /** Variante de pastille (.ukc-pill--*). */
    public function pill(): string
    {
        return match ($this) {
            self::None => 'muted',
            self::Scheduled => 'info',
            self::Live => 'err', // rouge « live »
            self::Ended => 'muted',
        };
    }
}
