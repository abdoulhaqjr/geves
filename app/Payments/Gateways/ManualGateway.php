<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;

/**
 * Paiement hors ligne : le participant règle sur place ou par virement ; le
 * paiement reste « en attente » jusqu'à confirmation par l'administration.
 * Toujours disponible (aucun identifiant requis).
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Paiement sur place / virement';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function initiate(Payment $payment): array
    {
        return [
            'type' => 'instructions',
            'message' => 'Votre demande est enregistrée. Réglez vos frais sur place ou par virement ; '
                .'votre paiement sera confirmé par l’organisation et votre reçu vous sera envoyé par e-mail.',
        ];
    }
}
