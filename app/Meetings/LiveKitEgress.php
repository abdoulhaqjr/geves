<?php

namespace App\Meetings;

use App\Models\EventSession;

/**
 * Client LiveKit Egress (enregistrement RoomComposite). Implémentation complète
 * au lot « Egress » : appel de l'API Egress LiveKit avec un jeton `roomRecord`
 * et une destination de stockage configurable (réglages admin).
 *
 * @phpstan-type EgressResult array{egressId?: string, message?: string}
 */
class LiveKitEgress
{
    /** @return EgressResult */
    public function start(EventSession $session): array
    {
        throw new \RuntimeException('Enregistrement non encore configuré.');
    }

    /** @return EgressResult */
    public function stop(EventSession $session): array
    {
        throw new \RuntimeException('Enregistrement non encore configuré.');
    }
}
