<?php

namespace App\Listeners;

use App\Actions\NotifySubmissionStatus;
use App\Events\SubmissionStatusChanged;

/**
 * Notifie l'auteur correspondant du nouveau statut de sa communication
 * (accusé de réception, décision…) et journalise l'envoi.
 */
class NotifyAuthorOfStatus
{
    public function __construct(protected NotifySubmissionStatus $notify) {}

    public function handle(SubmissionStatusChanged $event): void
    {
        $this->notify->handle($event->submission, $event->status, $event->note);
    }
}
