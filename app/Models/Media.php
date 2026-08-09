<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Média d'une édition : photo de galerie, vidéo, livret ou document.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
