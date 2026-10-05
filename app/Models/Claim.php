<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Réclamation ou incident signalé par un participant (souvent via l'assistante
 * Gesco quand elle ne peut pas résoudre le souci), traité au back-office.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $resolved_at
 * @property Event|null $event
 * @property User|null $user
 * @property User|null $assignee
 */
class Claim extends Model
{
    protected $guarded = ['id'];

    /** @return array<string, string> */
    public static function types(): array
    {
        return ['reclamation' => 'Réclamation', 'incident' => 'Incident'];
    }

    /** @return array<string, string> valeur => libellé */
    public static function statuses(): array
    {
        return [
            'open' => 'Ouverte',
            'in_progress' => 'En traitement',
            'resolved' => 'Résolue',
            'closed' => 'Clôturée',
        ];
    }

    /** @return array<string, string> variante de pastille par statut */
    public static function statusPills(): array
    {
        return ['open' => 'err', 'in_progress' => 'warn', 'resolved' => 'ok', 'closed' => 'muted'];
    }

    /** Catégories proposées (indicatives - l'IA/l'agent peut en choisir une autre). */
    public const CATEGORIES = ['paiement', 'soumission', 'inscription', 'badge', 'attestation', 'programme', 'technique', 'autre'];

    protected static function booted(): void
    {
        static::creating(function (Claim $claim) {
            if (blank($claim->reference)) {
                $claim->reference = self::generateReference($claim);
            }
        });
    }

    public static function generateReference(Claim $claim): string
    {
        $year = $claim->event?->year ?: date('Y');
        $prefix = $claim->type === 'incident' ? 'INC' : 'REC';

        do {
            $ref = $prefix.'-'.$year.'-'.Str::upper(Str::random(6));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    protected function casts(): array
    {
        return ['meta' => 'array', 'resolved_at' => 'datetime'];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst((string) $this->type);
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusPill(): string
    {
        return self::statusPills()[$this->status] ?? 'muted';
    }

    /**
     * Analyses IA successives (verdict + proposition), stockées dans meta.
     *
     * @return list<array<string, mixed>>
     */
    public function analyses(): array
    {
        $a = $this->meta['analyses'] ?? [];

        return is_array($a) ? array_values($a) : [];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
