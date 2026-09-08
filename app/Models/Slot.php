<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Créneau de passage : moment de présentation (salle, horaire) rattaché à une
 * communication acceptée.
 *
 * @property int $id
 * @property int $edition_id
 * @property int $submission_id
 * @property int $participant_id
 * @property int|null $session_id
 * @property string|null $session_label
 * @property string|null $room
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $chosen_by_participant
 */
class Slot extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'chosen_by_participant' => 'boolean',
        ];
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

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
