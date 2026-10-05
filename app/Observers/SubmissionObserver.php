<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Submission;
use Illuminate\Support\Str;

/**
 * Invariants d'une communication garantis à la création, quel que soit
 * l'appelant : référence unique (`ref_code`) et jeton d'accès (`access_token`).
 * Ne remplace jamais une valeur déjà fournie (tests, imports…).
 */
class SubmissionObserver
{
    public function creating(Submission $submission): void
    {
        if (blank($submission->access_token)) {
            $submission->access_token = Str::random(64);
        }

        if (blank($submission->ref_code)) {
            $submission->ref_code = $this->generateRefCode($submission);
        }
    }

    /** Référence lisible `AA-NNN` (préfixe = 2 derniers chiffres de l'année). */
    protected function generateRefCode(Submission $submission): string
    {
        $year = Event::whereKey($submission->event_id)->value('year');
        $prefix = $year ? substr((string) $year, -2) : 'XX';

        do {
            $seq = Submission::where('event_id', $submission->event_id)->count() + 1;
            $code = sprintf('%s-%03d', $prefix, $seq);
            $exists = Submission::where('ref_code', $code)->exists();
            if ($exists) {
                $code .= '-'.Str::upper(Str::random(2));
                $exists = Submission::where('ref_code', $code)->exists();
            }
        } while ($exists);

        return $code;
    }
}
