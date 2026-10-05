<?php

namespace App\Actions;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use App\Payments\GatewayRegistry;
use App\Support\EventPaymentConfig;
use Illuminate\Support\Facades\Log;

/**
 * Réconciliation des paiements en ligne : revérifie auprès de la passerelle le
 * statut des paiements restés « en attente », et les confirme le cas échéant.
 *
 * C'est le **filet de sécurité** du webhook : si une notification n'arrive jamais
 * (URL non déclarée chez le marchand, coupure réseau, erreur passerelle), le
 * participant qui a réellement payé serait sinon bloqué en « non payé ». La
 * vérification passe par `OnlineGateway::verify()` (appel serveur, jamais l'URL
 * de retour) avec les identifiants de l'ÉDITION du paiement.
 *
 * Planifiée toutes les 10 minutes (routes/console.php) ; lançable à la main :
 * `php artisan payments:reconcile`.
 */
class ReconcilePayments
{
    public function __construct(
        protected GatewayRegistry $registry,
        protected ConfirmPayment $confirm,
    ) {}

    /**
     * @param  int  $days  Ancienneté maximale des paiements réexaminés.
     * @param  int|null  $paymentId  Limiter à un paiement précis (bouton « Revérifier »).
     * @return array{checked: int, paid: int, failed: int, errors: int}
     */
    public function handle(int $days = 7, ?int $paymentId = null): array
    {
        $report = ['checked' => 0, 'paid' => 0, 'failed' => 0, 'errors' => 0];

        $payments = Payment::query()
            ->where('status', 'pending')
            ->whereNotNull('gateway_ref')
            ->when($paymentId, fn ($q) => $q->where('id', $paymentId))
            ->when(! $paymentId, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)))
            ->with('event')
            ->get();

        foreach ($payments as $payment) {
            $gw = $this->registry->get((string) $payment->gateway);
            if (! $gw instanceof OnlineGateway) {
                continue; // paiement manuel : rien à vérifier auprès d'une passerelle
            }

            $report['checked']++;

            try {
                $status = EventPaymentConfig::using($payment->event, fn () => $gw->verify($payment));
            } catch (\Throwable $e) {
                // Passerelle injoignable : on réessaiera au prochain passage.
                $report['errors']++;
                Log::channel('payments')->warning('Réconciliation : vérification impossible.', [
                    'payment' => $payment->id,
                    'gateway' => $payment->gateway,
                    'erreur' => $e->getMessage(),
                ]);

                continue;
            }

            $this->settle($payment, $status);

            if ($status === 'paid') {
                $report['paid']++;
                Log::channel('payments')->info('Réconciliation : paiement confirmé (webhook manquant ou tardif).', [
                    'payment' => $payment->id,
                    'gateway' => $payment->gateway,
                ]);
            } elseif ($status === 'failed') {
                $report['failed']++;
            }
        }

        return $report;
    }

    /**
     * Applique un statut VÉRIFIÉ à un paiement (idempotent, anti-double-paiement).
     * Source unique partagée par le retour navigateur, le webhook et la
     * réconciliation.
     */
    public function settle(Payment $payment, string $status): void
    {
        if ($status === 'paid') {
            try {
                $this->confirm->handle($payment);
            } catch (\DomainException) {
                // Déjà réglé (ou un autre paiement l'est) : rien à faire.
            }
        } elseif ($status === 'failed' && $payment->status === 'pending') {
            $payment->update(['status' => 'failed']);
        }
    }
}
