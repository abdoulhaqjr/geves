<?php

namespace App\Meetings\Providers;

use App\Enums\MeetingRole;
use App\Meetings\Contracts\Meetable;
use App\Meetings\Contracts\MeetingProvider;

/**
 * Lien de réunion externe fourni par l'organisateur (Zoom, Google Meet, une
 * autre instance…). La salle n'est pas gérée par la plateforme : on renvoie
 * simplement l'URL saisie (`meeting_url`). Non embarquable en iframe (ces
 * services bloquent généralement l'iframe) → ouverture dans un nouvel onglet.
 */
class CustomProvider implements MeetingProvider
{
    public function key(): string
    {
        return 'custom';
    }

    public function label(): string
    {
        return 'Lien externe';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function embeddable(): bool
    {
        return false;
    }

    public function joinUrl(Meetable $session, MeetingRole $role, ?string $displayName = null): string
    {
        return (string) $session->meetingUrl();
    }
}
