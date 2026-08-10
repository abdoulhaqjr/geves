<?php

namespace App\Actions;

use App\Models\Attestation;
use App\Models\Participant;
use Illuminate\Support\Str;

/**
 * Émet une attestation vérifiable pour un participant et un type donnés.
 * Idempotent : une seule attestation par (édition, participant, type).
 */
class IssueAttestation
{
    public function handle(Participant $participant, string $type): Attestation
    {
        $existing = Attestation::where('edition_id', $participant->edition_id)
            ->where('participant_id', $participant->id)
            ->where('type', $type)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Attestation::create([
            'uuid' => (string) Str::uuid(),
            'edition_id' => $participant->edition_id,
            'type' => $type,
            'participant_id' => $participant->id,
            'recipient_name' => $participant->full_name,
            'recipient_email' => $participant->email,
            'revoked' => false,
            'issued_at' => now(),
        ]);
    }
}
