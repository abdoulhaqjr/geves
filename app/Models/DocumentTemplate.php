<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle (mise en page JSON) d'un badge ou d'une attestation, par édition et type.
 *
 * @property array<string, mixed>|null $layout
 * @property array<string, mixed>|null $editor_state
 * @property Edition|null $edition
 */
class DocumentTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['layout' => 'array', 'editor_state' => 'array'];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
