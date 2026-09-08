<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote d'un compte sur un sondage de séance (un choix par compte).
 *
 * @property int $id
 * @property int $poll_id
 * @property int $account_id
 * @property int $choice index de l'option choisie
 */
class SessionPollVote extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['choice' => 'integer'];
    }

    /** @return BelongsTo<SessionPoll, $this> */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(SessionPoll::class, 'poll_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
