<?php

namespace App\Actions;

use App\Models\FormationEnrollment;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

/**
 * Applique un remboursement À GEVES : marque le paiement, RETIRE les droits
 * acquis (inscription formation, attestations) et laisse une trace. SOURCE
 * UNIQUE partagée par le geste manuel du back-office (`Admin\PaymentController::refund`)
 * et le webhook du prestataire (`PaymentController::webhook`, événement
 * `transaction.refunded`) - les deux doivent appliquer EXACTEMENT les mêmes
 * effets, sinon un remboursement fait chez FedaPay laisserait un participant
 * avec une « inscription réglée » et des attestations encore valides.
 *
 * ⚠️⚠️ **CE N'EST PAS LE VIREMENT.** GEVES ne renvoie pas l'argent (l'API du
 * prestataire n'expose aucune adresse de remboursement accessible à nos clés) :
 * le mouvement d'argent se fait au tableau de bord du prestataire. Ici on ne
 * touche QUE l'état de GEVES. D'où `argent_renvoye_par_geves: false` au journal.
 *
 * ⚠️ **Idempotent** : un paiement déjà `refunded` n'est pas retouché - le clic
 * manuel ET le webhook peuvent arriver tous les deux (dans n'importe quel ordre)
 * pour le même remboursement, sans rejouer la révocation ni réécrire la trace.
 */
class RefundPayment
{
    public function __construct(private RevokeAttestationsOnRefund $revoke) {}

    /**
     * @param  string  $source  'admin' (clic back-office) ou 'gateway' (webhook)
     * @param  string|null  $actorName  nom de l'humain qui rembourse (côté back-office)
     * @return int Nombre d'attestations révoquées (0 si déjà remboursé).
     */
    public function handle(Payment $payment, string $source = 'admin', ?string $actorName = null): int
    {
        if ($payment->status === 'refunded') {
            return 0; // déjà remboursé : on ne rejoue rien (webhook/clic en double)
        }

        $payment->update([
            'status' => 'refunded',
            'meta' => [...(array) $payment->meta,
                'refunded_at' => now()->toIso8601String(),
                'refunded_source' => $source,
                'refunded_by_name' => $actorName,
            ],
        ]);

        Log::channel('payments')->info('Paiement marqué remboursé.', [
            'payment' => $payment->id,
            'gateway' => $payment->gateway,
            'reference_passerelle' => (string) $payment->gateway_ref,
            'source' => $source,
            'par' => $actorName,
            'argent_renvoye_par_geves' => false,
        ]);

        // Rembourser doit RETIRER les droits acquis, sinon le participant garde une
        // « inscription réglée » et l'accès à la visio après remboursement. Miroir
        // exact de ce que ConfirmPayment applique à la confirmation.
        if ($payment->flow() === 'formation' && ($enrollmentId = $payment->meta['enrollment_id'] ?? null)) {
            FormationEnrollment::whereKey($enrollmentId)
                ->where('status', 'paid')
                ->update(['status' => 'registered']);
        }

        // Les attestations que ce paiement justifiait tombent avec lui, sauf si un
        // autre paiement réglé couvre encore le même droit.
        return $this->revoke->handle($payment);
    }
}
