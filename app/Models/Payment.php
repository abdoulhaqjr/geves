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
     * Moyens issus d'un parcours de paiement choisi par le payeur (clés des
     * passerelles). ⚠️ On ne nomme jamais l'agrégateur : `fedapay`/`cashpay` sont
     * des clés techniques, leur libellé reste générique. `manual` = la passerelle
     * « sur place / virement » (le payeur choisit, l'admin valide ensuite).
     */
    public const ONLINE_METHODS = [
        'fedapay' => 'En ligne · Mobile money / carte',
        'cashpay' => 'En ligne · Mobile money',
        'manual' => 'Sur place / virement (à valider)',
    ];

    /** Nature du paiement → libellé affiché (admin). */
    public const FLOWS = [
        'participation' => 'Participation',
        'formation' => 'Formation',
        'coauthor' => 'Co-auteur',
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

    /**
     * Nature du paiement (`meta.flow`) : « participation » par défaut, sinon
     * « coauthor » (co-auteur) ou « formation ». Détermine notamment la garde
     * anti-double-paiement (limitée à la participation).
     */
    public function flow(): string
    {
        $flow = $this->meta['flow'] ?? 'participation';

        return $flow === '' ? 'participation' : (string) $flow;
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

    /** Libellé de la nature du paiement (participation / formation / co-auteur). */
    public function flowLabel(): string
    {
        return self::FLOWS[$this->flow()] ?? ucfirst($this->flow());
    }

    /**
     * Objet précis du paiement : la nature, complétée du titre de la formation
     * pour un paiement de formation (l'admin doit pouvoir distinguer lequel).
     */
    public function flowDetail(): ?string
    {
        if ($this->flow() !== 'formation') {
            return null;
        }

        $id = (int) ($this->meta['formation_id'] ?? 0);

        return $id > 0 ? Formation::whereKey($id)->value('title') : null;
    }

    /**
     * Moyens de paiement groupés pour les filtres admin (évite une liste à plat
     * où « Manuel » et « Mobile money » se confondent).
     *
     * @return array<string, array<string, string>>
     */
    public static function methodGroups(): array
    {
        return [
            'Encaissement saisi par l’organisation' => self::MANUAL_METHODS,
            'Choisi par le payeur' => self::ONLINE_METHODS,
        ];
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

    /**
     * Mémorise l'opérateur mobile money choisi pour **cette tentative** (un
     * paiement en attente peut être repris avec un autre opérateur). Vide = laisser
     * le payeur choisir sur la page du prestataire.
     */
    public function setOperator(?string $operator): void
    {
        $operator = trim((string) $operator);
        $meta = $this->meta ?? [];

        if ($operator === '') {
            unset($meta['operator']);
        } else {
            $meta['operator'] = $operator;
        }

        $this->update(['meta' => $meta]);
    }

    /**
     * Coordonnées du payeur transmises à la passerelle pour **pré-remplir sa page**
     * (mobile money / carte). On lit le participant de l'édition, avec repli sur le
     * **compte unifié** lié : le participant peut avoir été créé sans téléphone
     * alors que le compte le porte (profil).
     *
     * @return array{firstname: ?string, lastname: ?string, email: ?string, phone: ?string}
     */
    public function payerContact(): array
    {
        $p = $this->participant;
        $u = $p?->user;

        $pick = fn (?string $a, ?string $b) => filled($a) ? $a : (filled($b) ? $b : null);

        return [
            'firstname' => $pick($p?->first_name, $u?->first_name),
            'lastname' => $pick($p?->last_name, $u?->last_name),
            'email' => $pick($p?->email, $u?->email),
            'phone' => $pick($p?->phone, $u?->phone),
        ];
    }
}
