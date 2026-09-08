<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relecture en aveugle d'une soumission par un relecteur (participant).
 *
 * @property array<string, int>|null $scores notes par critère de la grille
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
        return ['score' => 'decimal:2', 'scores' => 'array'];
    }

    public function recommendationLabel(): ?string
    {
        return self::RECOMMENDATIONS[$this->recommendation] ?? ($this->recommendation ?: null);
    }

    /**
     * Notes par critère (grille structurée).
     *
     * @return array<string, int>
     */
    public function criterionScores(): array
    {
        return is_array($this->scores) ? $this->scores : [];
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'reviewer_id');
    }
}
