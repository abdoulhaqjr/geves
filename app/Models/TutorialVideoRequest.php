<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Demande durable de création ou de mise à jour d'une vidéo de guide. */
class TutorialVideoRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['providers' => 'array', 'meta' => 'array'];
    }

    /** @return BelongsTo<TutorialGuide, $this> */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(TutorialGuide::class, 'tutorial_guide_id');
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }
}
