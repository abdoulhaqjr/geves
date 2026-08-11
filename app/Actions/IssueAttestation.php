<?php

namespace App\Actions;

use App\Models\Attestation;
use App\Models\Participant;
use App\Support\Notifier;

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

        // uuid généré par AttestationObserver.
        $attestation = Attestation::create([
            'edition_id' => $participant->edition_id,
            'type' => $type,
            'participant_id' => $participant->id,
            'recipient_name' => $participant->full_name,
            'recipient_email' => $participant->email,
            'revoked' => false,
            'issued_at' => now(),
        ]);

        Notifier::account(
            $participant->account,
            kind: 'attestation',
            title: 'Attestation disponible',
            body: 'Votre attestation ('.$attestation->typeLabel().') peut être téléchargée.',
            url: route('account.documents'),
            icon: 'award',
        );

        return $attestation;
    }
}
