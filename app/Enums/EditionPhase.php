<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;
use App\Models\Edition;

/**
 * Phase du cycle de vie d'une édition (guide §2.2), dérivée des signaux existants
 * (publication, dates de l'événement, ouverture/clôture des soumissions).
 *
 * ⚠️ Piège métier : un simple appel à communications ouvert n'est PAS « en cours »
 * - l'édition n'est « en cours » que lorsque le déroulement a commencé (dates de
 * l'événement atteintes). {@see Edition::phase()}.
 *
 * Correspondance avec les 3 vues publiques ({@see publicView()}) :
 *  - à venir  ← call_open · under_review · registration
 *  - en cours ← ongoing
 *  - passé    ← closed
 *  - (brouillon : non publié, invisible)
 */
enum EditionPhase: string
{
    use HasLabels;

    case Draft = 'draft';
    case CallOpen = 'call_open';
    case UnderReview = 'under_review';
    case Registration = 'registration';
    case Ongoing = 'ongoing';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::CallOpen => 'Appel à communications',
            self::UnderReview => 'Évaluation en cours',
            self::Registration => 'Inscriptions ouvertes',
            self::Ongoing => 'Colloque en cours',
            self::Closed => 'Colloque passé',
        };
    }

    /** Variante de pastille (.ukc-pill--*). */
    public function pill(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::CallOpen => 'ok',
            self::UnderReview => 'warn',
            self::Registration => 'info',
            self::Ongoing => 'live',
            self::Closed => 'closed',
        };
    }

    /**
     * Vue publique correspondante : « upcoming » (à venir), « ongoing » (en cours),
     * « past » (passé) ou null (brouillon, non affiché).
     */
    public function publicView(): ?string
    {
        return match ($this) {
            self::Draft => null,
            self::CallOpen, self::UnderReview, self::Registration => 'upcoming',
            self::Ongoing => 'ongoing',
            self::Closed => 'past',
        };
    }

    /** Est-ce une édition passée (archives) ? */
    public function isPast(): bool
    {
        return $this === self::Closed;
    }

    /** L'événement se déroule-t-il en ce moment ? */
    public function isOngoing(): bool
    {
        return $this === self::Ongoing;
    }
}
