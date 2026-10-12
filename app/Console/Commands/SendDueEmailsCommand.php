<?php

namespace App\Console\Commands;

use App\Actions\SendDueEmails;
use Illuminate\Console\Command;

/**
 * Envoie les e-mails programmés arrivés à échéance (campagnes et rappels).
 * Planifiée chaque minute dans routes/console.php ; peut aussi être lancée à
 * la main : `php artisan emails:send`.
 */
class SendDueEmailsCommand extends Command
{
    protected $signature = 'emails:send {--limit= : Nombre maximum d’e-mails à envoyer sur ce passage}';

    protected $description = 'Envoie les e-mails programmés (campagnes/rappels) arrivés à échéance';

    public function handle(SendDueEmails $action): int
    {
        $limit = $this->option('limit');
        $report = $action->handle($limit !== null ? (int) $limit : null);

        $this->info(sprintf(
            'E-mails envoyés : %d - échecs : %d (dus : %d).',
            $report['sent'],
            $report['failed'],
            $report['total'],
        ));

        return self::SUCCESS;
    }
}
