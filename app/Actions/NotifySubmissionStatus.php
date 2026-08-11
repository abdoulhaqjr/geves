<?php

namespace App\Actions;

use App\Mail\SubmissionStatusMail;
use App\Models\Email;
use App\Models\Submission;
use App\Support\Notifier;
use Illuminate\Support\Facades\Mail;

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
        if (! Notifier::emailAllowed($submission->participant?->account, 'submission_status')) {
            return;
        }

        $mail = new SubmissionStatusMail($submission, $statusKey, $note);

        Mail::to($recipient)->send($mail);

        Email::create([
            'edition_id' => $submission->edition_id,
            'participant_id' => $submission->participant_id,
            'template' => 'submission_'.$statusKey,
            'recipient' => $recipient,
            'subject' => $mail->meta['subject'],
            'body' => $note,
            'sent_at' => now(),
            'status' => 'sent',
        ]);
    }
}
