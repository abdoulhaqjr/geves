<?php

namespace App\Actions;

use App\Mail\SubmissionStatusMail;
use App\Models\Email;
use App\Models\Submission;
use App\Support\EmailTemplates;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoie l'e-mail de statut à l'auteur correspondant d'une soumission et
 * journalise l'envoi dans la table `emails`.
 */
class NotifySubmissionStatus
{
    public function handle(Submission $submission, string $statusKey, ?string $note = null): void
    {
        $recipient = $submission->correspondingAuthorEmail();

        if (! $recipient) {
            return;
        }

        // Préférence e-mail du compte auteur (le cas échéant) : interrupteur unique.
        if (! Notifier::emailAllowed($submission->participant?->user, 'submission_status')) {
            return;
        }

        $email = Email::create([
            'event_id' => $submission->event_id,
            'participant_id' => $submission->participant_id,
            'template' => 'submission_'.$statusKey,
            'recipient' => $recipient,
            'subject' => EmailTemplates::forSubmission($submission, $statusKey)['subject'],
            'body' => $note,
            'status' => 'queued',
        ]);

        try {
            Mail::to($recipient)->send(
                (new SubmissionStatusMail($submission, $statusKey, $note, $email->id))->afterCommit()
            );
        } catch (Throwable $exception) {
            $email->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);

            throw $exception;
        }
    }
}
