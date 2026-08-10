<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Statuts d'un paiement.
 */
enum PaymentStatus: string
{
    use HasLabels;

    case Pending = 'pending';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Réglé',
            self::Refunded => 'Remboursé',
            self::Failed => 'Échoué',
        };
    }

    /** Variante de pastille (.ukc-pill--*). */
    public function pill(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Paid => 'ok',
            self::Refunded => 'muted',
            self::Failed => 'err',
        };
    }
}
