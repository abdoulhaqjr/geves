<?php

namespace App\Models;

use App\Observers\AttestationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Attestation émise (participation, communication, modération, rapporteur) avec
 * UUID de vérification publique.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $issued_at
 * @property Carbon|null $delivered_at
 * @property string|null $credential
 * @property string|null $key_id
 * @property Carbon|null $signed_at
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $first_viewed_at
 * @property Carbon|null $last_viewed_at
 * @property int $views_count
 * @property Carbon|null $shared_at
 * @property int $shares_count
 * @property Carbon|null $linkedin_at
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
        'formation_animation' => 'Animation de formation',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isValid(): bool
    {
        return ! $this->revoked;
    }

    /**
     * Remise en main propre ? (délivrance papier)
     *
     * ⚠️ **Émettre n'est pas remettre.** Une attestation peut être émise,
     * imprimée, et attendre des mois dans une chemise parce que personne ne
     * sait ce qui reste à distribuer.
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Titre de la formation que cette attestation porte (vide sinon).
     *
     * ⚠️ `meta` d'abord : il est **figé à l'émission**, alors que la formation
     * peut avoir été renommée depuis. Une attestation doit dire ce qu'elle disait
     * le jour où elle a été délivrée, pas ce que le catalogue dit aujourd'hui.
     */
    public function formationTitle(): string
    {
        return (string) ($this->meta['formation_title'] ?? '')
            ?: (string) $this->formation?->title;
    }

    /**
     * Le TITULAIRE a-t-il accusé réception ?
     *
     * ⚠️ Pas « quelqu'un l'a ouverte » : le lien d'une attestation circule, et
     * l'ouverture d'un recruteur n'est pas une réception. Seul le titulaire
     * connecté à son espace pose cet accusé (cf. RecordAttestationEvent).
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /** L'attestation a-t-elle été ouverte en ligne, par qui que ce soit hors staff ? */
    public function wasOpened(): bool
    {
        return $this->first_viewed_at !== null;
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
            'delivered_at' => 'datetime',
            'signed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'shared_at' => 'datetime',
            'linkedin_at' => 'datetime',
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

    /**
     * Journal de vie de l'attestation : ouvertures, partages, départ LinkedIn.
     *
     * @return HasMany<AttestationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(AttestationEvent::class);
    }

    /** Formation concernée (attestations de formation / animation ; sinon null). */
    /** @return BelongsTo<Formation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }
}
