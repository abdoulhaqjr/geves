<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Qui peut regarder la diffusion d'une session : tout le monde (public) ou les
 * seuls participants inscrits de l'édition. Le gating fin (ex. payés seulement)
 * pourra être affiné à l'arrivée du mockup — cf. EventSession::canBeWatchedBy().
 */
enum StreamVisibility: string
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
