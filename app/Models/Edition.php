<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Édition : une occurrence datée d'un colloque, avec son cycle de vie et son
 * contenu public. Les réglages libres (frais, contenu enrichi…) sont en JSON.
 */
class Edition extends Model
{
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

    public function colloque(): BelongsTo
    {
        return $this->belongsTo(Colloque::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ateliers(): HasMany
    {
        return $this->hasMany(Atelier::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class);
    }

    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function committeeMembers(): HasMany
    {
        return $this->hasMany(CommitteeMember::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function isPublished(): bool
    {
        return in_array($this->status, ['open', 'closed', 'archived'], true);
    }
}
