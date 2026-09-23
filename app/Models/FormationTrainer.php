<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Formateur d'une formation. Lié au compte unifié (`users`) s'il en a un (par
 * e-mail), sinon saisie libre (nom/e-mail/affiliation).
 *
 * @property array<string, mixed>|null $meta
 * @property Formation|null $formation
 * @property User|null $user
 */
class FormationTrainer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_moderator' => 'boolean',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Formation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
