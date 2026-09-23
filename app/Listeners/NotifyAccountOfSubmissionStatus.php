<?php

namespace App\Listeners;

use App\Enums\SubmissionStatus;
use App\Events\SubmissionStatusChanged;
use App\Support\Notifier;

/**
 * Crée une notification in-app pour le compte de l'auteur correspondant quand
 * une décision est prise sur sa communication (en relecture, correction,
 * acceptée, refusée). Complète l'e-mail (NotifyAuthorOfStatus) sans le remplacer.
 * Auto-découvert par Laravel - ne pas enregistrer manuellement.
 */
class NotifyAccountOfSubmissionStatus
{
    public function handle(SubmissionStatusChanged $event): void
    {
        $submission = $event->submission;
        $account = $submission->participant?->user;

        if (! $account) {
            return;
        }

        $label = SubmissionStatus::tryFrom($event->status)?->label() ?? $event->status;

        Notifier::account(
            $account,
            kind: 'submission_status',
            title: 'Communication : '.$label,
            body: '« '.$submission->title.' »',
            url: route('account.submissions.show', $submission),
            icon: 'inbox',
        );
    }
}
