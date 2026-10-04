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
 * @property Carbon|null $presented_at
 * @property int|null $presented_by
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
            'presented_at' => 'datetime',
        ];
    }

    /**
     * Le passage a-t-il été constaté par qui tenait la séance ?
     *
     * ⚠️ C'est ce constat qui ouvre les attestations des auteurs : il ne se
     * déduit pas de l'horaire passé. Un créneau peut être dépassé sans que la
     * communication ait eu lieu - l'auteur ne s'est pas connecté, la séance a
     * pris du retard, le sujet a été reporté.
     */
    public function wasPresented(): bool
    {
        return $this->presented_at !== null;
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
