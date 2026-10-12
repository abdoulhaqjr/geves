<?php

namespace App\Actions;

use App\Models\Email;
use App\Models\Event;
use App\Models\Participant;
use App\Support\EmailAudience;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Met en file une campagne d'e-mails : crée une ligne `emails` (statut
 * « queued ») par destinataire de l'audience, avec sujet et corps personnalisés.
 * Les e-mails sont ensuite envoyés à échéance par la commande `emails:send`.
 */
class QueueEmailCampaign
{
    /**
     * @return int Nombre d'e-mails mis en file.
     */
    public function handle(
        Event $event,
        string $audience,
        string $subject,
        string $body,
        ?Carbon $scheduledAt = null,
    ): int {
        $recipients = EmailAudience::resolve($event, $audience);
        $when = $scheduledAt ?? now();
        $batchId = (string) Str::uuid();
        $count = 0;

        foreach ($recipients as $participant) {
            Email::create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'template' => 'campaign',
                'audience' => $audience,
                'batch_id' => $batchId,
                'recipient' => $participant->email,
                'subject' => $this->fill($subject, $participant),
                'body' => $this->fill($body, $participant),
                'scheduled_at' => $when,
                'status' => 'queued',
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Remplace les variables par les valeurs du participant : ancien style
     * `{prenom}` et balises de fusion du mockup `{{participant_name}}`.
     */
    protected function fill(string $text, Participant $participant): string
    {
        $event = $participant->event;
        $dates = $event && $event->date_start
            ? trim($event->date_start->format('d/m/Y').($event->date_end ? ' - '.$event->date_end->format('d/m/Y') : ''))
            : '';

        return strtr($text, [
            // Ancien style.
            '{prenom}' => (string) $participant->first_name,
            '{nom}' => (string) $participant->last_name,
            '{nom_complet}' => $participant->full_name,
            '{event}' => (string) ($event?->title ?? ''),
            // Balises de fusion (mockup admin-emails).
            '{{participant_name}}' => $participant->full_name,
            '{{participant_email}}' => (string) $participant->email,
            '{{edition_name}}' => (string) ($event?->title ?? ''),
            '{{edition_dates}}' => $dates,
            '{{login_url}}' => url('/mon-espace'),
            '{{communication_title}}' => '',
            '{{amount}}' => '',
            '{{payment_ref}}' => '',
            '{{attestation_url}}' => '',
            '{{badge_url}}' => '',
            '{{session_date}}' => '',
            '{{session_room}}' => '',
        ]);
    }
}
