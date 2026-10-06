<?php

namespace App\Console\Commands;

use App\Enums\MeetingStatus;
use App\Models\EventSession;
use Illuminate\Console\Command;

/**
 * Réconcilie le statut des visios de séance avec l'horloge, sans intervention
 * manuelle : une visio « programmée » passe « en direct » à l'approche de son
 * horaire, et toute visio « programmée » ou « en direct » dont la fenêtre est
 * passée devient « terminée ».
 *
 * Avant, une séance restait « programmée » jusqu'à ce que quelqu'un la bascule
 * à la main, et une séance de la veille restait joignable en « en direct ».
 * Cette commande (planifiée toutes les 5 minutes dans routes/console.php) met le
 * statut à jour. La bascule elle-même vit dans
 * {@see EventSession::syncMeetingStatus()}, aussi appelée en auto-réparation sur
 * les chemins d'accès (join/token/chat, modération, programme).
 *
 * À la main : `php artisan meetings:sync`.
 */
class SyncMeetingStatuses extends Command
{
    protected $signature = 'meetings:sync';

    protected $description = 'Réconcilie le statut des visios de séance (programmée → en direct → terminée) avec l’horaire';

    public function handle(): int
    {
        $changed = 0;

        // Seules les visios « programmée » et « en direct » peuvent évoluer ; peu
        // de séances à un instant donné. L'itération permet d'appliquer la fenêtre
        // horaire (colonnes nullables) sans la recoder en SQL.
        EventSession::whereIn('meeting_status', [MeetingStatus::Scheduled->value, MeetingStatus::Live->value])
            ->get()
            ->each(function (EventSession $session) use (&$changed) {
                if ($session->syncMeetingStatus()) {
                    $changed++;
                }
            });

        $this->info($changed.' visio(s) mise(s) à jour.');

        return self::SUCCESS;
    }
}
