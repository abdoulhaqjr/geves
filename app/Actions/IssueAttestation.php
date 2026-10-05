<?php

namespace App\Actions;

use App\Documents\AttestationCertifier;
use App\Models\Attestation;
use App\Models\Formation;
use App\Models\Participant;
use App\Models\Submission;
use App\Sms\SmsManager;
use App\Support\AttestationPolicy;
use App\Support\Notifier;

/**
 * Émet une attestation vérifiable pour un participant et un type donnés.
 * Idempotent : une seule attestation par (événement, participant, type) - et, pour
 * une attestation de communication, par (événement, participant, type, communication)
 * puisqu'une personne a une attestation par communication associée.
 *
 * À l'émission, l'attestation est scellée par une signature cryptographique de
 * l'émetteur (cf. AttestationCertifier) si une clé est configurée. Fail-open :
 * en l'absence de clé, l'attestation est tout de même émise, non signée.
 */
class IssueAttestation
{
    public function __construct(
        private ?AttestationCertifier $certifier = null,
        private ?SmsManager $sms = null,
    ) {}

    public function handle(Participant $participant, string $type, ?Submission $submission = null, ?Formation $formation = null): Attestation
    {
        $existing = Attestation::where('event_id', $participant->event_id)
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
            'event_id' => $participant->event_id,
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
        $this->announce($attestation, $participant);

        return $attestation;
    }

    /**
     * Prévient le bénéficiaire - si l'événement délivre en ligne.
     *
     * ⚠️ **Rien en mode papier.** L'exemplaire qui fait foi est celui qu'on remet
     * en main propre : annoncer un document qu'on ne peut pas encore retirer ne
     * fait qu'amener la personne au secrétariat. L'attestation reste visible dans
     * son espace - lui fermer aussi cette porte n'y renverrait que ses questions.
     */
    private function announce(Attestation $attestation, Participant $participant): void
    {
        $event = $attestation->event;

        if (! AttestationPolicy::notifies($event)) {
            return;
        }

        // In-app, et e-mail si la personne l'a laissé ouvert (cf. SystemNotification).
        Notifier::account(
            $participant->user,
            kind: 'attestation',
            title: 'Attestation disponible',
            body: 'Votre attestation ('.$attestation->typeLabel().') peut être téléchargée.',
            url: route('account.documents'),
            icon: 'award',
        );

        if (! AttestationPolicy::sendsSms($event)) {
            return;
        }

        $this->maybeSms(
            $participant->phone ?: $participant->user?->phone,
            'Votre attestation ('.$attestation->typeLabel().') est disponible dans votre espace '.config('app.name').'.',
        );
    }

    /**
     * Envoi SMS best-effort.
     *
     * ⚠️ Rien sans numéro NI sans passerelle configurée : le fournisseur « log »
     * est celui du développement, et laisser croire qu'un SMS est parti parce
     * qu'une ligne a été journalisée serait pire que de ne rien envoyer.
     * ⚠️ Un échec n'interrompt JAMAIS l'émission : l'attestation existe, elle est
     * signée, et la personne la trouvera dans son espace.
     */
    private function maybeSms(?string $phone, string $message): void
    {
        $phone = trim((string) $phone);

        if ($phone === '' || config('sms.default', 'log') === 'log') {
            return;
        }

        try {
            ($this->sms ?? app(SmsManager::class))->send($phone, $message);
        } catch (\Throwable) {
            // Best-effort : un SMS perdu ne doit pas faire échouer une émission.
        }
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
