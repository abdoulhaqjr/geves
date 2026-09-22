<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lien entre un auteur (correspondant ou co-auteur) d'une communication et un
 * compte utilisateur, quand ce dernier existe (ou est invité à s'inscrire).
 *
 * @property int $submission_id
 * @property int $edition_id
 * @property int|null $user_id
 * @property string $role
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $affiliation
 * @property string|null $match_type
 * @property string $status
 * @property Carbon|null $notified_at
 * @property Carbon|null $invited_at
 * @property Submission|null $submission
 * @property User|null $user
 */
class SubmissionAuthor extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime', 'invited_at' => 'datetime'];
    }

    public function isCorresponding(): bool
    {
        return $this->role === 'corresponding';
    }

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
