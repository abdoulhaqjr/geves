<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Rapport d'une séance rédigé par son rapporteur (une par séance).
 *
 * @property Carbon|null $submitted_at
 * @property EventSession|null $session
 * @property Edition|null $edition
 */
class SessionReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /** @return BelongsTo<EventSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
