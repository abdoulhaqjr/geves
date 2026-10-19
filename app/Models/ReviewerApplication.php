<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewerApplication extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    /**
     * ⚠️ Les relations sont NOMMÉES : sans la forme générique, l'analyse ne voit
     * qu'un `Model` au bout, et tout ce qu'on lit dessus (`->email`, `->name`…)
     * devient une propriété inconnue chez l'appelant - neuf erreurs dans
     * `SubmissionController` pour deux annotations manquantes ici.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
