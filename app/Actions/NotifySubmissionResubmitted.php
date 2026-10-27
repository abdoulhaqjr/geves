<?php

namespace App\Actions;

use App\Models\Submission;
use App\Support\EventManagers;
use App\Support\Notifier;

/**
 * Prévient les gestionnaires qu'une communication en correction a été resoumise.
 *
 * ⚠️ La resoumission repasse le statut de « Correction demandée » à « Soumise »
 * (version `v+1`) : sans ce signal, une correction qui revient se noie parmi les
 * dépôts neufs, et personne au back-office n'est averti ({@see
 * \App\Http\Controllers\Account\SubmissionController::resubmit()} ne notifiait
 * que l'auteur). Ciblage {@see EventManagers::forEvent()} - gestionnaires et
 * co-gestionnaires de l'événement, repli administrateurs - plutôt qu'une
 * diffusion à tout le staff : ce sont eux qui réexaminent. Type
 * `submission_resubmitted` : in-app + e-mail générique, décochable.
 */
class NotifySubmissionResubmitted
{
    public function handle(Submission $submission): void
    {
        // ⚠️ Branche explicite plutôt que `?->` sur `participant` : Larastan type
        // la relation BelongsTo comme non-nulle et y verrait un nullsafe inutile.
        $who = (string) $submission->correspondingAuthorName();
        if ($who === '' && $submission->participant) {
            $who = (string) $submission->participant->full_name;
        }

        $title = 'Correction resoumise - '.$submission->ref_code;
        $body = '« '.$submission->title.' » - v'.$submission->version
            .($who !== '' ? ' - '.$who : '');

        foreach (EventManagers::forEvent($submission->event_id) as $user) {
            Notifier::user(
                $user,
                'submission_resubmitted',
                $title,
                $body,
                route('admin.submissions.show', $submission),
                'pen',
            );
        }
    }
}
