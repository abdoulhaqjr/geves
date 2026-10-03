<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Réaction d'une personne à un message du fil.
 *
 * `reactor` identifie qui réagit d'une connexion à l'autre : le compte quand il
 * y en a un, le membre du staff sinon. ⚠️ Pas l'identité LiveKit, qui change à
 * chaque connexion - la même personne compterait alors deux fois.
 */
class MeetingMessageReaction extends Model
{
    protected $fillable = ['meeting_message_id', 'reactor', 'emoji'];

    /** @return BelongsTo<MeetingMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(MeetingMessage::class, 'meeting_message_id');
    }
}
