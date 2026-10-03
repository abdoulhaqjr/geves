<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un message du fil d'une salle de visio.
 *
 * ⚠️ Le fil est archivé pour être **rejoué à l'arrivée** : sans lui, qui rejoint
 * en cours de séance ne voit rien de ce qui s'est dit. Le canal de données de
 * LiveKit ne sert plus qu'à l'instantané (les personnes déjà connectées).
 */
class MeetingMessage extends Model
{
    /**
     * Réactions proposées, et liste FERMÉE de ce qui peut être enregistré.
     *
     * ⚠️ Jamais l'emoji envoyé par le client tel quel : une colonne libre
     * laisserait écrire n'importe quoi dans le fil de la salle.
     */
    public const EMOJIS = ['👍', '❤️', '😄', '👏', '❓'];

    protected $fillable = [
        'uid', 'user_id', 'author_name', 'body',
        'reply_uid', 'reply_author', 'reply_excerpt',
    ];

    /** @return MorphTo<Model, $this> */
    public function meetable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<MeetingMessageReaction, $this> */
    public function reactions(): HasMany
    {
        return $this->hasMany(MeetingMessageReaction::class);
    }
}
