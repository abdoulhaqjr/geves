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

    protected function casts(): array
    {
        return ['score' => 'decimal:2'];
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
