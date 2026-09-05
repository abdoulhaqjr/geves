<?php

namespace App\Models;

use App\Enums\EditionPhase;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Édition : une occurrence datée d'un colloque, avec son cycle de vie et son
 * contenu public. Les réglages libres (frais, contenu enrichi…) sont en JSON.
 *
 * @property array<string, mixed>|null $settings réglages libres (frais, axes, contenu public…)
 * @property Carbon|null $date_start
 * @property Carbon|null $date_end
 * @property Carbon|null $submission_deadline
 * @property int $year
 * @property bool $submission_open
 * @property string $status
 */
class Edition extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public const STATUSES = ['draft', 'open', 'closed', 'archived'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'date_start' => 'date',
            'date_end' => 'date',
            'submission_deadline' => 'datetime',
            'submission_open' => 'boolean',
            'year' => 'integer',
        ];
    }

    /** @return BelongsTo<Colloque, $this> */
    public function colloque(): BelongsTo
    {
        return $this->belongsTo(Colloque::class);
    }

    /** @return HasMany<Participant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /** @return HasMany<Submission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Atelier, $this> */
    public function ateliers(): HasMany
    {
        return $this->hasMany(Atelier::class);
    }

    /** @return HasMany<EventSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    /** @return HasMany<Slot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    /** @return HasMany<Attestation, $this> */
    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class);
    }

    /** @return HasMany<Email, $this> */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /** @return HasMany<CommitteeMember, $this> */
    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    /** @return HasMany<Media, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['open', 'closed', 'archived'], true);
    }

    /**
     * Phase du cycle de vie (guide §2.2), dérivée sans requête depuis le statut de
     * publication, les dates de l'événement et l'ouverture des soumissions.
     * Source unique pour les libellés/pastilles et les 3 vues publiques.
     */
    public function phase(): EditionPhase
    {
        if ($this->status === 'draft') {
            return EditionPhase::Draft;
        }
        // Clôture explicite par l'admin.
        if ($this->status === 'archived' || $this->status === 'closed') {
            return EditionPhase::Closed;
        }

        $today = Carbon::today();
        $now = Carbon::now();

        // Déroulement : les dates de l'événement sont atteintes.
        if ($this->date_start && $this->date_end
            && $this->date_start->lte($today) && $this->date_end->gte($today)) {
            return EditionPhase::Ongoing;
        }
        // Événement passé (date de fin dépassée).
        if ($this->date_end && $this->date_end->lt($today)) {
            return EditionPhase::Closed;
        }

        // Pré-événement (futur ou sans dates) : phase selon l'appel à communications.
        if ($this->submission_open
            && (! $this->submission_deadline || $this->submission_deadline->gte($now))) {
            return EditionPhase::CallOpen;
        }
        if ($this->submission_deadline && $this->submission_deadline->lt($now)) {
            return EditionPhase::UnderReview; // soumissions closes → évaluation
        }

        return EditionPhase::Registration; // pas d'appel actif, événement à venir → inscriptions
    }
}
