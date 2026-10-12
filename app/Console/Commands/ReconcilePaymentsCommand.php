<?php

namespace App\Console\Commands;

use App\Actions\ReconcilePayments;
use Illuminate\Console\Command;

/**
 * Revérifie auprès des passerelles les paiements en ligne restés « en attente »
 * et confirme ceux qui sont réellement réglés (filet de sécurité si un webhook
 * n'est jamais arrivé). Planifiée toutes les 10 minutes dans routes/console.php ;
 * à la main : `php artisan payments:reconcile [--days=7] [--payment=12]`.
 */
class ReconcilePaymentsCommand extends Command
{
    protected $signature = 'payments:reconcile
        {--days=7 : Ancienneté maximale (en jours) des paiements réexaminés}
        {--payment= : Ne revérifier qu’un paiement précis (son id)}';

    protected $description = 'Revérifie les paiements en ligne en attente auprès des passerelles';

    public function handle(ReconcilePayments $action): int
    {
        $payment = $this->option('payment');
        $report = $action->handle(
            (int) $this->option('days'),
            $payment !== null ? (int) $payment : null,
        );

        $this->info(sprintf(
            'Paiements vérifiés : %d - confirmés : %d - échoués : %d - injoignables : %d.',
            $report['checked'],
            $report['paid'],
            $report['failed'],
            $report['errors'],
        ));

        return self::SUCCESS;
    }
}
