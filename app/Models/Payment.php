<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement des frais d'inscription (mobile money, carte via passerelle, ou
 * encaissement manuel). Un règlement par participant et par édition.
 * Statuts : {@see PaymentStatus}.
 */
class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** Moyens d'encaissement manuel. */
    public const MANUAL_METHODS = [
        'cash' => 'Espèces',
        'transfer' => 'Virement',
        'deposit' => 'Dépôt',
        'mobile_money' => 'Mobile money (hors ligne)',
        'check' => 'Chèque',
        'other' => 'Autre',
    ];

    /** @return array<string, string> valeur => libellé. */
    public static function statuses(): array
    {
        return PaymentStatus::options();
    }

    /** @return array<string, string> valeur => variante de pastille. */
    public static function statusPills(): array
    {
        return PaymentStatus::pills();
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** Statut typé (ou null si valeur inconnue). */
    public function statusEnum(): ?PaymentStatus
    {
        return PaymentStatus::tryFrom((string) $this->status);
    }

    public function statusLabel(): string
    {
        return $this->statusEnum()?->label() ?? (string) $this->status;
    }

    public function statusPill(): string
    {
        return $this->statusEnum()?->pill() ?? 'muted';
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
