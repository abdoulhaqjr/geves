<?php

namespace App\Actions;

use App\Models\Attestation;
use App\Models\Participant;
use App\Models\Review;

/** Rétablit une attestation révoquée uniquement lors du retrait de l'évaluateur. */
class RestoreReviewerInstructionAttestation
{
    public function handle(Participant $reviewer): void
    {
        $meta = $reviewer->meta ?? [];
        if ($reviewer->role !== 'reviewer' || empty($meta['instruction_attestation_revoked_with_reviewer'])) {
            return;
        }

        if (! Review::where('reviewer_id', $reviewer->id)
            ->where('status', 'completed')
            ->whereHas('submission', fn ($query) => $query->where('event_id', $reviewer->event_id))
            ->exists()) {
            return;
        }

        Attestation::where('event_id', $reviewer->event_id)
            ->where('participant_id', $reviewer->id)
            ->where('type', 'instruction_resumes')
            ->where('revoked', true)
            ->update(['revoked' => false]);

        unset($meta['instruction_attestation_revoked_with_reviewer']);
        $reviewer->update(['meta' => $meta]);
    }
}
