<?php

namespace App\Actions;

use App\Models\Edition;
use App\Models\Email;
use App\Models\Participant;
use App\Support\EmailAudience;
use Illuminate\Support\Carbon;

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
        Edition $edition,
        string $audience,
        string $subject,
        string $body,
        ?Carbon $scheduledAt = null,
    ): int {
        $recipients = EmailAudience::resolve($edition, $audience);
        $when = $scheduledAt ?? now();
        $count = 0;

        foreach ($recipients as $participant) {
            Email::create([
                'edition_id' => $edition->id,
                'participant_id' => $participant->id,
                'template' => 'campaign',
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

    /** Remplace les variables {prenom} {nom} {nom_complet} {edition} par les valeurs du participant. */
    protected function fill(string $text, Participant $participant): string
    {
        return strtr($text, [
            '{prenom}' => (string) $participant->first_name,
            '{nom}' => (string) $participant->last_name,
            '{nom_complet}' => $participant->full_name,
            '{edition}' => (string) ($participant->edition?->title ?? ''),
        ]);
    }
}
