<?php

namespace App\Observers;

use App\Models\Participant;
use Illuminate\Support\Str;

/**
 * Invariant d'un participant : jeton d'accès (`access_token`) généré à la
 * création s'il n'est pas fourni (lien de suivi par jeton, portail évaluateur).
 */
class ParticipantObserver
{
    public function creating(Participant $participant): void
    {
        if (blank($participant->access_token)) {
            $participant->access_token = Str::random(64);
        }
    }
}
