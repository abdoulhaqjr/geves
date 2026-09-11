<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Question du public posée pendant une séance (Q&A live). Votée par le public,
 * modérée par le modérateur (répondue / masquée).
 *
 * @property int $id
 * @property int $session_id
 * @property int $edition_id
 * @property int|null $account_id
 * @property int|null $user_id
 * @property string $author_name
 * @property string $body
 * @property string $status
 * @property Carbon|null $answered_at
 * @property int|null $votes_count agrégat withCount('votes')
 */
class SessionQuestion extends Model
{
    protected $guarded = ['id'];

    public const STATUSES = ['pending', 'answered', 'hidden'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime'];
    }

    public function isHidden(): bool
    {
        return $this->status === 'hidden';
    }

    public function isAnswered(): bool
    {
        return $this->status === 'answered';
    }

    /** @return BelongsTo<EventSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SessionQuestionVote, $this> */
    public function votes(): HasMany
    {
        return $this->hasMany(SessionQuestionVote::class, 'question_id');
    }
}
