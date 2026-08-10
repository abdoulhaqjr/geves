<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attestation émise (participation, communication, modération, rapporteur) avec
 * UUID de vérification publique.
 */
class Attestation extends Model
{
    protected $guarded = ['id'];

    /** Types d'attestation. */
    public const TYPES = [
        'participation' => 'Participation',
        'communication' => 'Communication',
        'moderation' => 'Modération',
        'rapporteur' => 'Rapporteur',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isValid(): bool
    {
        return ! $this->revoked;
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'revoked' => 'boolean',
            'issued_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
