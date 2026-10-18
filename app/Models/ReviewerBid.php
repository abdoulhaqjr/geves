<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Candidature d'un évaluateur sur une soumission (« bid ») : préférence
 * d'évaluation + auto-déclaration de conflit d'intérêt. Exprimée depuis le
 * portail évaluateur (en aveugle) et prise en compte par l'attribution auto.
 *
 * @property Submission|null $submission
 * @property Participant|null $reviewer
 */
class ReviewerBid extends Model
{
    public const INTERESTED = 'interested';

    public const NEUTRAL = 'neutral';

    public const UNAVAILABLE = 'unavailable';

    /** Libellés (portail évaluateur). */
    public const INTERESTS = [
        self::INTERESTED => 'Intéressé(e)',
        self::NEUTRAL => 'Neutre',
        self::UNAVAILABLE => 'Indisponible',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['conflict' => 'boolean'];
    }

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'reviewer_id');
    }
}
