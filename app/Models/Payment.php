<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement des frais d'inscription (mobile money, carte via passerelle, ou
 * encaissement manuel). Un règlement par participant et par édition.
 */
class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** Statuts d'un paiement. */
    public const STATUSES = [
        'pending' => 'En attente',
        'paid' => 'Réglé',
        'refunded' => 'Remboursé',
        'failed' => 'Échoué',
    ];

    public const STATUS_PILLS = [
        'pending' => 'warn',
        'paid' => 'ok',
        'refunded' => 'muted',
        'failed' => 'err',
    ];

    /** Moyens d'encaissement manuel. */
    public const MANUAL_METHODS = [
        'cash' => 'Espèces',
        'transfer' => 'Virement',
        'deposit' => 'Dépôt',
        'mobile_money' => 'Mobile money (hors ligne)',
        'check' => 'Chèque',
        'other' => 'Autre',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusPill(): string
    {
        return self::STATUS_PILLS[$this->status] ?? 'muted';
    }

    public function methodLabel(): string
    {
        return self::MANUAL_METHODS[$this->method] ?? ucfirst((string) $this->method);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
