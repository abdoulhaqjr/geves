<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sondage live d'une séance : question + options, voté par le public.
 *
 * @property int $id
 * @property int $session_id
 * @property int $event_id
 * @property string $question
 * @property list<string> $options
 * @property string $status
 */
class SessionPoll extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = ['open', 'closed'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /** @return BelongsTo<EventSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    /** @return HasMany<SessionPollVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(SessionPollVote::class, 'poll_id');
    }
}
