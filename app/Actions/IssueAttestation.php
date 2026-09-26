<?php

namespace App\Actions;

use App\Documents\AttestationCertifier;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\Participant;
use App\Models\Submission;
use App\Support\Notifier;

/**
 * Émet une attestation vérifiable pour un participant et un type donnés.
 * Idempotent : une seule attestation par (édition, participant, type) - et, pour
 * une attestation de communication, par (édition, participant, type, communication)
 * puisqu'une personne a une attestation par communication associée.
 *
 * À l'émission, l'attestation est scellée par une signature cryptographique de
 * l'émetteur (cf. AttestationCertifier) si une clé est configurée. Fail-open :
 * en l'absence de clé, l'attestation est tout de même émise, non signée.
 */
class IssueAttestation
{
    public function __construct(private ?AttestationCertifier $certifier = null) {}

    public function handle(Participant $participant, string $type, ?Submission $submission = null, ?Formation $formation = null): Attestation
    {
        $existing = Attestation::where('edition_id', $participant->edition_id)
            ->where('participant_id', $participant->id)
            ->where('type', $type)
            ->where('submission_id', $submission?->id)
            ->where('formation_id', $formation?->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $meta = [];
        if ($submission) {
            $meta['communication_title'] = (string) $submission->title;
            $meta['communication_ref'] = (string) $submission->ref_code;
        }
        if ($formation) {
            $meta['formation_title'] = (string) $formation->title;
            $meta['formation_theme'] = (string) ($formation->theme ?? '');
            // Formateurs + signatures figés à l'émission : l'attestation ne doit pas
            // changer si l'équipe de formateurs évolue ensuite.
            $trainers = $formation->trainers()->orderBy('id')->get();
            $meta['formation_trainers'] = $trainers->map(fn ($t) => $t->displayName())->all();
            $meta['formation_signatures'] = $trainers
                ->filter(fn ($t) => $t->signatureUrl() !== null)
                ->map(fn ($t) => ['name' => $t->displayName(), 'src' => $t->signatureUrl()])
                ->values()->all();
        }

        // uuid généré par AttestationObserver.
        $attestation = Attestation::create([
            'edition_id' => $participant->edition_id,
            'type' => $type,
            'participant_id' => $participant->id,
            'submission_id' => $submission?->id,
            'formation_id' => $formation?->id,
            'recipient_name' => $participant->full_name,
            'recipient_email' => $participant->email,
            'meta' => $meta ?: null,
            'revoked' => false,
            'issued_at' => now(),
        ]);

        $this->certify($attestation);

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

    /**
     * Scelle l'attestation avec la signature de l'émetteur (best-effort).
     * Fail-open : un échec de signature ne doit jamais empêcher l'émission.
     */
    private function certify(Attestation $attestation): void
    {
        $certifier = $this->certifier ?? app(AttestationCertifier::class);

        try {
            $signed = $certifier->sign($attestation);
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        if ($signed === null) {
            return;
        }

        $attestation->forceFill([
            'credential' => $signed['jws'],
            'key_id' => $signed['kid'],
            'signed_at' => now(),
        ])->save();
    }
}
