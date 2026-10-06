<?php

namespace App\Console\Commands;

use App\Enums\MeetingStatus;
use App\Models\EventSession;
use Illuminate\Console\Command;

/**
 * Clôture les visios « en direct » dont l'horaire est passé : `live → ended`.
 *
 * Une séance de la veille restait joignable et affichait « En direct » tant que
 * personne ne changeait son statut à la main. Cette commande (planifiée toutes
 * les 5 minutes dans routes/console.php) remet le statut à jour et coupe l'accès.
 * La bascule elle-même vit dans {@see EventSession::closeMeetingIfExpired()},
 * aussi appelée en auto-réparation sur les chemins d'accès (join/token/chat).
 *
 * À la main : `php artisan meetings:close-ended`.
 */
class CloseEndedMeetings extends Command
{
    protected $signature = 'meetings:close-ended';

    protected $description = 'Clôture (live → terminée) les visios de séance dont l’horaire est passé';

    public function handle(): int
    {
        $closed = 0;

        // Peu de séances « en direct » à un instant donné ; l'itération permet
        // d'appliquer la fenêtre horaire (colonnes nullables) sans la recoder en SQL.
        EventSession::where('meeting_status', MeetingStatus::Live->value)
            ->get()
            ->each(function (EventSession $session) use (&$closed) {
                if ($session->closeMeetingIfExpired()) {
                    $closed++;
                }
            });

        $this->info($closed.' visio(s) clôturée(s).');

        return self::SUCCESS;
    }
}
