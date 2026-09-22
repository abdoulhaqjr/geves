<?php

namespace App\Models;

use App\Observers\AttestationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Attestation émise (participation, communication, modération, rapporteur) avec
 * UUID de vérification publique.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $issued_at
 * @property string|null $credential
 * @property string|null $key_id
 * @property Carbon|null $signed_at
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

    /** L'attestation porte-t-elle une signature cryptographique ? */
    public function isSigned(): bool
    {
        return filled($this->credential);
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'revoked' => 'boolean',
            'issued_at' => 'datetime',
            'signed_at' => 'datetime',
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

    /** Communication concernée (attestations de communication uniquement ; sinon null). */
    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
