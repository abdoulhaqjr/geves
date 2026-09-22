<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Témoignage d'une personne ayant participé à un événement. Modéré au niveau
 * plateforme (`pending` → `approved`/`rejected`), puis un sous-ensemble
 * `approved` + `featured` est affiché sur la page d'accueil (ordre `position`,
 * puis `impact_score` proposé par l'IA).
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $edition_id
 * @property string $author_name
 * @property string|null $author_role
 * @property string|null $author_affiliation
 * @property string $quote
 * @property int|null $rating
 * @property string $status
 * @property bool $featured
 * @property int $position
 * @property int|null $impact_score
 * @property string|null $impact_reason
 * @property bool $consent
 * @property Carbon|null $moderated_at
 */
class Testimonial extends Model
{
    protected $guarded = ['id'];

    /** Statuts de modération. */
    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
    ];

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'consent' => 'boolean',
            'moderated_at' => 'datetime',
        ];
    }

    /** Témoignages affichés sur l'accueil : approuvés, mis en avant, ordonnés. */
    /** @param Builder<Testimonial> $query */
    public function scopeForHome(Builder $query): void
    {
        $query->where('status', 'approved')
            ->where('featured', true)
            ->orderBy('position')
            ->orderByDesc('impact_score')
            ->orderByDesc('id');
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
