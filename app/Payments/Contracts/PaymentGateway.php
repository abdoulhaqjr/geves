<?php

namespace App\Payments\Contracts;

use App\Models\Payment;

/**
 * Contrat commun aux passerelles de paiement. Une passerelle s'annonce
 * (clé, libellé, disponibilité) et sait initier un paiement.
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    /** Vrai si les identifiants marchands sont configurés. */
    public function isAvailable(): bool;

    /**
     * Initie le paiement.
     *
     * @return array{type: 'redirect'|'instructions', url?: string, message?: string}
     */
    public function initiate(Payment $payment): array;
}
