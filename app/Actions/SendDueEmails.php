<?php

namespace App\Actions;

use App\Mail\CampaignMail;
use App\Models\Email;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les e-mails programmés arrivés à échéance : les lignes `emails` au
 * statut « queued » dont `scheduled_at` est passé. Chaque envoi est marqué
 * « sent » (ou « failed » avec l'erreur), garantissant l'idempotence - un
 * e-mail déjà envoyé n'est jamais renvoyé par un passage ultérieur.
 *
 * @phpstan-type SendReport array{sent:int,failed:int,total:int}
 */
class SendDueEmails
{
    /**
     * @return array{sent:int,failed:int,total:int}
     */
    public function handle(?int $limit = null): array
    {
        $due = Email::query()
            ->where('status', 'queued')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->orderBy('id')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($due as $email) {
            try {
                Mail::to($email->recipient)->send(
                    new CampaignMail((string) $email->subject, (string) $email->body)
                );
                $email->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                $sent++;
            } catch (\Throwable $e) {
                $email->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => $due->count()];
    }
}
