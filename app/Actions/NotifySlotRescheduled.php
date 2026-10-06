<?php

namespace App\Actions;

use App\Models\Slot;
use App\Support\Notifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Prévient l'auteur d'une communication que son créneau de passage a été
 * reprogrammé (notification in-app + e-mail selon ses préférences).
 *
 * ⚠️ À n'appeler QUE depuis une action déclenchée par un humain, et seulement
 * quand le créneau porte le drapeau transitoire {@see Slot::$rescheduledNow} :
 * on ne notifie JAMAIS depuis l'évènement du modèle, sinon un seed de volume ou
 * un rejeu idempotent enverrait des centaines de messages.
 */
class NotifySlotRescheduled
{
    public function handle(Slot $slot): void
    {
        if (! $slot->rescheduledNow) {
            return;
        }

        $user = $slot->loadMissing('participant.user', 'submission')->participant?->user;
        if ($user === null) {
            return; // auteur sans compte : rien à notifier
        }

        $change = $slot->lastReschedule();
        if ($change === null) {
            return;
        }

        $title = Str::limit((string) (data_get($slot, 'submission.title') ?? 'votre communication'), 70);
        $when = $this->newSchedule($slot);

        $body = 'Le créneau de passage de « '.$title.' » a été modifié.'
            .($when !== '' ? ' Nouveau créneau : '.$when.'.' : '')
            .' Consultez le détail dans votre espace.';

        $url = $slot->submission_id > 0
            ? route('account.submissions.show', $slot->submission_id)
            : route('account.submissions.index');

        Notifier::account($user, kind: 'schedule_change', title: 'Votre créneau de passage a changé', body: $body, url: $url, icon: 'calendar');
    }

    /** « lundi 6 octobre de 14h00 à 14h30, salle 5 », ou chaîne vide. */
    private function newSchedule(Slot $slot): string
    {
        if (! $slot->starts_at instanceof Carbon) {
            return '';
        }

        $hours = $slot->starts_at->locale('fr')->translatedFormat('l j F \à H\hi')
            .($slot->ends_at ? ' - '.$slot->ends_at->format('H\hi') : '');
        $room = filled($slot->room) ? ', salle '.$slot->room : '';

        return $hours.$room;
    }
}
