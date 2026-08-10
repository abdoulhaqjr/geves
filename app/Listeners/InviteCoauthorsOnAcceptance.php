<?php

namespace App\Listeners;

use App\Actions\NotifyCoauthorsForPayment;
use App\Events\SubmissionStatusChanged;

/**
 * À l'acceptation d'une communication, invite ses co-auteurs à régler leurs
 * frais (au tarif de leur grade) via le parcours co-auteur.
 */
class InviteCoauthorsOnAcceptance
{
    public function __construct(protected NotifyCoauthorsForPayment $notify) {}

    public function handle(SubmissionStatusChanged $event): void
    {
        if ($event->status !== 'accepted') {
            return;
        }

        $this->notify->handle($event->submission);
    }
}
