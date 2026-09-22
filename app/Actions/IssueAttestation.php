<?php

namespace App\Actions;

use App\Models\Attestation;
use App\Models\Participant;
use App\Models\Submission;
use App\Support\Notifier;

/**
 * Émet une attestation vérifiable pour un participant et un type donnés.
 * Idempotent : une seule attestation par (édition, participant, type) — et, pour
 * une attestation de communication, par (édition, participant, type, communication)
 * puisqu'une personne a une attestation par communication associée.
 */
class IssueAttestation
{
    public function handle(Participant $participant, string $type, ?Submission $submission = null): Attestation
    {
        $existing = Attestation::where('edition_id', $participant->edition_id)
            ->where('participant_id', $participant->id)
            ->where('type', $type)
            ->where('submission_id', $submission?->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // uuid généré par AttestationObserver.
        $attestation = Attestation::create([
            'edition_id' => $participant->edition_id,
            'type' => $type,
            'participant_id' => $participant->id,
            'submission_id' => $submission?->id,
            'recipient_name' => $participant->full_name,
            'recipient_email' => $participant->email,
            'meta' => $submission ? [
                'communication_title' => (string) $submission->title,
                'communication_ref' => (string) $submission->ref_code,
            ] : null,
            'revoked' => false,
            'issued_at' => now(),
        ]);

        Notifier::account(
            $participant->user,
            kind: 'attestation',
            title: 'Attestation disponible',
            body: 'Votre attestation ('.$attestation->typeLabel().') peut être téléchargée.',
            url: route('account.documents'),
            icon: 'award',
        );

        return $attestation;
    }
}
