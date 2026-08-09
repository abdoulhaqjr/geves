<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membre de comité ou panéliste (organisation, scientifique, supervision).
 */
class CommitteeMember extends Model
{
    protected $guarded = ['id'];

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
