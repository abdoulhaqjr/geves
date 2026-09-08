<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Présence effective d'un participant à une séance (pointage par scan de badge
 * en « mode séance »). Une ligne par (séance, participant).
 *
 * @property int $id
 * @property int $edition_id
 * @property int $session_id
 * @property int $participant_id
 * @property int|null $scanned_by
 * @property Carbon|null $scanned_at
 */
class SessionAttendance extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<EventSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
