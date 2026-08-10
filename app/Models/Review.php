<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relecture en aveugle d'une soumission par un relecteur (participant).
 */
class Review extends Model
{
    protected $guarded = ['id'];

    /** Recommandations possibles d'un relecteur. */
    public const RECOMMENDATIONS = [
        'accept' => 'Accepter en l’état',
        'minor' => 'Révisions mineures',
        'major' => 'Révisions majeures',
        'reject' => 'Rejeter',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
    }

    public function recommendationLabel(): ?string
    {
        return self::RECOMMENDATIONS[$this->recommendation] ?? ($this->recommendation ?: null);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'reviewer_id');
    }
}
