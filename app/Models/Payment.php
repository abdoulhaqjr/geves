<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Paiement des frais d'inscription (mobile money, carte via passerelle, ou
 * encaissement manuel). Un règlement par participant et par édition.
 * Statuts : {@see PaymentStatus}.
 *
 * @property string|null $reference
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $meta
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

    /**
     * Moyens EN LIGNE (passerelles). FedaPay est un agrégateur : mobile money
     * (MTN, Moov, Togocom…) et cartes bancaires via une page hébergée.
     */
    public const ONLINE_METHODS = [
        'fedapay' => 'En ligne · Mobile Money / Carte (FedaPay)',
        'cashpay' => 'En ligne · CashPay',
        'manual' => 'Manuel',
    ];

    /**
     * Attribue une référence unique dès la création (tous flux : manuel, en
     * ligne, co-auteur…), même avant confirmation.
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (blank($payment->reference)) {
                $payment->reference = self::generateReference($payment);
            }
        });
    }

    /** Référence unique de la forme PAY-<année>-<6 car.>. */
    public static function generateReference(Payment $payment): string
    {
        $year = $payment->edition?->year ?: date('Y');

        do {
            $ref = 'PAY-'.$year.'-'.Str::upper(Str::random(6));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    /** Libellés combinés (filtre admin) : moyens manuels + en ligne. */
    public static function methodLabels(): array
    {
        return self::MANUAL_METHODS + self::ONLINE_METHODS;
    }

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
        return self::MANUAL_METHODS[$this->method]
            ?? self::ONLINE_METHODS[$this->method]
            ?? ucfirst((string) $this->method);
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
