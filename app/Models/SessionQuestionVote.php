<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vote (« +1 ») d'un compte sur une question de séance. Un par (question, compte).
 *
 * @property int $id
 * @property int $question_id
 * @property int $account_id
 */
class SessionQuestionVote extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<SessionQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(SessionQuestion::class, 'question_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
