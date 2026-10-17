<?php

namespace App\Console\Commands;

use App\Actions\FreezeEventIdentities;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * Gèle (ou regèle) l'identité des participants d'un événement : l'instantané
 * utilisé par les badges pour ne plus suivre une modification de profil faite
 * après la clôture. Normalement déclenché automatiquement à l'archivage ; cette
 * commande sert à :
 *   - geler un événement déjà archivé AVANT l'arrivée de cette fonctionnalité ;
 *   - RAFRAÎCHIR l'instantané après une correction (sans désarchiver / ré-archiver).
 *
 *   php artisan events:freeze-identities            # tous les événements archivés
 *   php artisan events:freeze-identities 12         # l'événement 12 (quel que soit son statut)
 */
class FreezeEventIdentitiesCommand extends Command
{
    protected $signature = 'events:freeze-identities
        {event? : Identifiant de l’événement (défaut : tous les événements archivés)}';

    protected $description = 'Gèle l’identité des participants (instantané pour les badges d’un événement clos).';

    public function handle(FreezeEventIdentities $action): int
    {
        $events = $this->argument('event') !== null
            ? Event::where('id', $this->argument('event'))->get()
            : Event::where('status', 'archived')->get();

        if ($events->isEmpty()) {
            $this->warn('Aucun événement à geler.');

            return self::SUCCESS;
        }

        foreach ($events as $event) {
            $frozen = $action->handle($event);
            $this->line("Événement « {$event->title} » : {$frozen} participant(s) gelé(s).");
        }

        return self::SUCCESS;
    }
}
