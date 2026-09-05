<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membre de comité ou panéliste (organisation, scientifique, supervision).
 *
 * @property string $role
 * @property string $person_name
 * @property string|null $person_title
 * @property string|null $institution
 * @property string|null $photo_url
 * @property int $ordre
 * @property Edition|null $edition
 */
class CommitteeMember extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
