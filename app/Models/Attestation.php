<?php

namespace App\Models;

use App\Observers\AttestationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attestation émise (participation, communication, modération, rapporteur) avec
 * UUID de vérification publique.
 */
#[ObservedBy([AttestationObserver::class])]
class Attestation extends Model
{
    protected $guarded = ['id'];

    /** Types d'attestation. */
    public const TYPES = [
        'soumission' => 'Soumission',
        'acceptation' => 'Acceptation',
        'participation' => 'Participation',
        'communication' => 'Communication',
        'moderation' => 'Modération',
        'rapporteur' => 'Rapporteur',
        'formation' => 'Formation',
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
