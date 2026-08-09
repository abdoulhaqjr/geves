<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Soumission d'un résumé : titre, co-auteurs (JSON), axe, type, statut du
 * workflow de relecture, versionnage (resoumission).
 */
class Submission extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'meta' => 'array',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
