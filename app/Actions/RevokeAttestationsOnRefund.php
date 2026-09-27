<?php

namespace App\Actions;

use App\Models\Attestation;
use App\Models\Payment;

/**
 * Remboursement → retrait des attestations que ce paiement justifiait.
 *
 * Une attestation atteste d'un droit acquis par un paiement. Si ce paiement est
 * remboursé et qu'aucun autre ne le remplace, l'attestation n'a plus de
 * fondement : elle est **révoquée** (jamais supprimée - une attestation est
 * vérifiable publiquement, elle doit rester traçable, la page de vérification
 * la signalant alors comme révoquée).
 *
 * ⚠️ On ne révoque que s'il ne reste **aucun paiement réglé** couvrant le même
 * droit : un participant peut avoir réglé deux fois (tentative + encaissement
 * manuel), ou s'être inscrit à plusieurs formations.
 */
class RevokeAttestationsOnRefund
{
    /** @return int Nombre d'attestations révoquées. */
    public function handle(Payment $payment): int
    {
        $participantId = (int) $payment->participant_id;
        if ($participantId === 0) {
            return 0;
        }

        return $payment->flow() === 'formation'
            ? $this->forFormation($payment, $participantId)
            : $this->forParticipation($payment, $participantId);
    }

    /**
     * Frais de formation : l'attestation de formation tombe si plus aucun
     * paiement réglé ne couvre CETTE formation pour cette personne.
     *
     * L'attestation d'animation (`formation_animation`) n'est pas concernée :
     * un formateur ne paie pas, son attestation ne dépend d'aucun paiement.
     */
    private function forFormation(Payment $payment, int $participantId): int
    {
        $formationId = (int) ($payment->meta['formation_id'] ?? 0);
        if ($formationId === 0) {
            return 0;
        }

        $stillPaid = Payment::where('participant_id', $participantId)
            ->where('status', 'paid')
            ->where('meta', 'like', '%"flow":"formation"%')
            ->where('meta', 'like', '%"formation_id":'.$formationId.'%')
            ->exists();

        if ($stillPaid) {
            return 0;
        }

        return Attestation::where('participant_id', $participantId)
            ->where('edition_id', $payment->edition_id)
            ->where('formation_id', $formationId)
            ->where('type', 'formation')
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    /**
     * Frais de participation (co-auteur inclus : il règle pour participer) :
     * l'attestation de participation ET les attestations de communication
     * tombent ensemble, faute d'inscription réglée.
     */
    private function forParticipation(Payment $payment, int $participantId): int
    {
        $stillPaid = Payment::where('participant_id', $participantId)
            ->where('status', 'paid')
            ->forParticipation()
            ->exists();

        if ($stillPaid) {
            return 0;
        }

        return Attestation::where('participant_id', $participantId)
            ->where('edition_id', $payment->edition_id)
            ->whereIn('type', ['participation', 'communication'])
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }
}
