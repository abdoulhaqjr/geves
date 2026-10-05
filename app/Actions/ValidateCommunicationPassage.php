<?php

namespace App\Actions;

use App\Models\Slot;
use App\Models\User;
use App\Support\AttestationPolicy;
use App\Support\CommunicationAttestations;

/**
 * **Constat du passage d'une communication**, posé par qui tient la séance.
 *
 * C'est le geste qui ouvre les attestations de ses auteurs : en délivrance en
 * ligne, chacun reçoit la sienne dans la foulée. Le constat se fait au moment
 * où la communication vient d'avoir lieu - pas à la fin du colloque, quand plus
 * personne ne se souvient de qui est monté à la tribune.
 *
 * ⚠️ **Irréversible côté séance, et c'est voulu.** Une attestation partie ne se
 * reprend pas : elle est signée, elle porte un QR de vérification, et son
 * destinataire l'a peut-être déjà transmise. Une erreur se répare au
 * back-office, en **révoquant** l'attestation - un geste tracé, par quelqu'un
 * dont c'est la responsabilité.
 *
 * ⚠️ **Idempotent.** Deux modérateurs peuvent valider le même passage à une
 * seconde d'intervalle (la salle et la page de modération sont deux portes sur
 * le même geste) : le second constat ne réémet rien.
 */
class ValidateCommunicationPassage
{
    public function __construct(private IssueAttestation $issue) {}

    /**
     * @return array{already: bool, issued: int, online: bool}
     */
    public function handle(Slot $slot, ?User $par): array
    {
        $submission = $slot->submission;
        $event = $slot->event ?? $submission?->event;
        $online = AttestationPolicy::issuesOnPassage($event);

        if ($slot->wasPresented()) {
            return ['already' => true, 'issued' => 0, 'online' => $online];
        }

        $slot->forceFill(['presented_at' => now(), 'presented_by' => $par?->id])->save();

        // ⚠️ Le constat est posé AVANT l'émission, et séparément : si l'émission
        // échoue à mi-parcours, le passage reste constaté (il a bien eu lieu) et
        // le gestionnaire reprend le lot au back-office. L'inverse - un passage
        // perdu parce qu'un envoi a échoué - ferait disparaître le fait.
        if (! $submission || ! $online) {
            return ['already' => false, 'issued' => 0, 'online' => $online];
        }

        $issued = 0;
        foreach (CommunicationAttestations::forSubmission($submission, $slot->session) as $ayant) {
            $this->issue->handle($ayant['participant'], 'communication', $submission);
            $issued++;
        }

        return ['already' => false, 'issued' => $issued, 'online' => $online];
    }
}
