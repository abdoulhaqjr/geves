<?php

namespace App\Models;

use App\Actions\ConfirmPayment;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Paiement des frais d'inscription (mobile money, carte via passerelle, ou
 * encaissement manuel). Un règlement par participant et par événement.
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
     * passerelles). ⚠️ On ne nomme jamais l'agrégateur : `fedapay` est une clé
     * technique, son libellé reste générique. `manual` = la passerelle
     * « sur place / virement » (le payeur choisit, l'admin valide ensuite).
     *
     * ⚠️ **`cashpay` n'est plus une passerelle, et son libellé reste ici.** La
     * passerelle a été retirée du projet, mais un paiement DÉJÀ encaissé par
     * elle garde sa clé en base : sans ce libellé, l'historique l'afficherait
     * sans nom. Ne rien proposer sous cette clé - le registre ne la connaît
     * plus, elle n'est donc plus sélectionnable.
     */
    public const ONLINE_METHODS = [
        'fedapay' => 'En ligne - Mobile money / carte',
        'cashpay' => 'En ligne - Mobile money',
        'manual' => 'Sur place / virement (à valider)',
    ];

    /**
     * Natures **affichées** en back-office. ⚠️ Un paiement de co-auteur est un
     * paiement de participation (le co-auteur règle pour participer) : il est donc
     * présenté et filtré comme tel, la nuance n'apparaissant qu'en détail. La
     * valeur technique `meta.flow = 'coauthor'` reste distincte en base : elle
     * pilote la garde anti-double de {@see ConfirmPayment}.
     */
    public const FLOWS = [
        'participation' => 'Participation',
        'formation' => 'Formation',
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
        $year = $payment->event?->year ?: date('Y');

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

    /**
     * Restreint aux paiements qui ouvrent les **droits de participation** à
     * l'événement (badge, attestations, accès) : tout sauf les formations.
     *
     * ⚠️ Un paiement de formation est rattaché au même `Participant` : sans ce
     * filtre, régler une formation débloquerait à tort la participation.
     * Le co-auteur est inclus : il règle bien pour participer.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeForParticipation(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('meta', 'not like', '%"flow":"formation"%')
            ->orWhereNull('meta'));
    }

    /** Nature affichée : « formation », sinon « participation » (co-auteur inclus). */
    public function displayFlow(): string
    {
        return $this->flow() === 'formation' ? 'formation' : 'participation';
    }

    /** Libellé de la nature affichée (Participation / Formation). */
    public function flowLabel(): string
    {
        return self::FLOWS[$this->displayFlow()];
    }

    /**
     * Précision sur l'objet : le titre de la formation réglée, ou la mention
     * « Co-auteur » qui distingue ce cas particulier de participation.
     */
    public function flowDetail(): ?string
    {
        if ($this->flow() === 'coauthor') {
            return 'Co-auteur';
        }

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

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
     * (mobile money / carte). On lit le participant de l'événement, avec repli sur le
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
