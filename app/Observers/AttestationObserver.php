<?php

namespace App\Observers;

use App\Models\Attestation;
use Illuminate\Support\Str;

/**
 * Invariant d'une attestation : identifiant public de vérification (`uuid`)
 * généré à la création s'il n'est pas fourni.
 */
class AttestationObserver
{
    public function creating(Attestation $attestation): void
    {
        if (blank($attestation->uuid)) {
            $attestation->uuid = (string) Str::uuid();
        }
    }
}
