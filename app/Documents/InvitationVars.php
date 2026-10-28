<?php

namespace App\Documents;

use App\Models\Participant;
use App\Support\EventSettings;

/**
 * Valeurs de fusion de la lettre d'invitation (rendu PDF joint à l'e-mail).
 *
 * Calquée sur {@see AttestationVars}, mais SANS les valeurs propres à un
 * certificat (identifiant, URL de vérification, communication, formation) : une
 * invitation n'atteste de rien, elle convie. L'identité vient de
 * `Participant::eventIdentity()` (gelée si l'événement l'est, en direct sinon),
 * le signataire des mêmes réglages que les attestations.
 */
class InvitationVars
{
    /** @return array<string, string> */
    public static function for(Participant $participant): array
    {
        // ⚠️ Larastan type les relations BelongsTo comme NON-nulles (`?->` y
        // déclenche nullsafe.neverNull, et `?? ''` sur les clés garanties de
        // eventIdentity() déclenche nullCoalesce.offset) : un participant a
        // toujours son événement. On lit donc en `->`, et `(string)` absorbe une
        // valeur de colonne nulle (titre, lieu, thème).
        $event = $participant->event;
        $identity = $participant->eventIdentity();

        return [
            'org' => (string) $event->series->name,
            'event' => (string) $event->title,
            'year' => (string) ($event->year ?: ''),
            'location' => (string) $event->location,
            'theme' => (string) $event->theme,
            'dates' => (string) ($event->dateRangeLabel() ?? ''),
            'name' => (string) $identity['name'],
            'civility' => (string) $identity['civility'],
            'institution' => (string) $identity['institution'],
            // Date du jour rédigée en toutes lettres (« 28 octobre 2026 »), locale
            // forcée au français comme sur les attestations.
            'date' => now()->locale('fr')->translatedFormat('j F Y'),
            'signatory' => (string) EventSettings::get($event, 'attestations.signatory', ''),
            'signatory_role' => (string) EventSettings::get($event, 'attestations.signatory_role', ''),
        ];
    }
}
