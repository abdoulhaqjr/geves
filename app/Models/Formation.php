<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Formation proposée dans le cadre d'une édition (module activable). Une édition
 * peut en proposer plusieurs ; chacune a ses formateurs, ses frais (0 = gratuite),
 * son lieu/salle, ses dates et, à terme, sa visio.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Edition|null $edition
 */
class Formation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published' => 'boolean',
            'public' => 'boolean',
            'meta' => 'array',
        ];
    }

    /** Formation gratuite (aucun frais) : inscription sans paiement. */
    public function isFree(): bool
    {
        return (float) $this->fee <= 0.0;
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasMany<FormationTrainer, $this> */
    public function trainers(): HasMany
    {
        return $this->hasMany(FormationTrainer::class);
    }
}
