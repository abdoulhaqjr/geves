<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Statuts du workflow d’évaluation d'une communication.
 */
enum SubmissionStatus: string
{
    use HasLabels;

    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Revision = 'revision';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Soumise',
            self::UnderReview => 'En évaluation',
            self::Revision => 'Correction demandée',
            self::Accepted => 'Acceptée',
            self::Rejected => 'Refusée',
        };
    }

    /** Variante de pastille (.ukc-pill--*). */
    public function pill(): string
    {
        return match ($this) {
            self::Submitted => 'info',
            self::UnderReview, self::Revision => 'warn',
            self::Accepted => 'ok',
            self::Rejected => 'err',
        };
    }
}
