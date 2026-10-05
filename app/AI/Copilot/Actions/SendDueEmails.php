<?php

namespace App\AI\Copilot\Actions;

use App\Actions\SendDueEmails as SendDueEmailsAction;
use App\AI\Copilot\CopilotAction;
use App\Models\Email;
use App\Models\Event;
use App\Models\User;

/**
 * Déclenche l'envoi des e-mails programmés arrivés à échéance (campagnes /
 * envois planifiés au statut « queued » dont la date est passée). Idempotent :
 * un e-mail déjà envoyé n'est jamais renvoyé.
 */
class SendDueEmails implements CopilotAction
{
    public function key(): string
    {
        return 'send_due_emails';
    }

    public function label(): string
    {
        return 'Envoyer les e-mails programmés dus';
    }

    public function capability(): string
    {
        return 'emails';
    }

    public function description(): string
    {
        return 'Envoie immédiatement les e-mails programmés arrivés à échéance (statut « en file » dont la '
            ."date d'envoi est passée). À proposer si l'utilisateur veut déclencher / forcer l'envoi des "
            .'e-mails planifiés en attente.';
    }

    public function summary(array $params, Event $event): string
    {
        return 'Envoyer maintenant '.$this->dueCount().' e-mail(s) programmé(s) arrivé(s) à échéance.';
    }

    public function context(Event $event): ?string
    {
        return $this->dueCount().' e-mail(s) programmé(s) arrivé(s) à échéance, prêt(s) à être envoyés.';
    }

    public function execute(array $params, Event $event, User $user): string
    {
        $report = app(SendDueEmailsAction::class)->handle();

        if ($report['total'] === 0) {
            return 'Aucun e-mail programmé n’était arrivé à échéance.';
        }

        return $report['sent'].' e-mail(s) envoyé(s)'
            .($report['failed'] > 0 ? ', '.$report['failed'].' en échec' : '').'.';
    }

    private function dueCount(): int
    {
        return (int) Email::query()
            ->where('status', 'queued')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->count();
    }
}
