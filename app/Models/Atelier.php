<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Atelier : regroupe un ou plusieurs axes thématiques d'une édition et contient
 * une ou plusieurs sessions.
 *
 * @property array<int, string>|null $tracks
 * @property Edition|null $edition
 */
class Atelier extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tracks' => 'array'];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasMany<EventSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class);
    }
}
