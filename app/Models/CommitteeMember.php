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
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $qualite
 * @property string|null $photo_url
 * @property int $ordre
 * @property Event|null $event
 */
class CommitteeMember extends Model
{
    protected $guarded = ['id'];

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
