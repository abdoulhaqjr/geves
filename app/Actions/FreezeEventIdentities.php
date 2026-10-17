<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\Participant;
use Illuminate\Support\Facades\Storage;

/**
 * Gèle l'identité des participants d'un événement (instantané par participant
 * dans `participants.meta['identity']`), pris à l'**archivage** de l'événement.
 *
 * Pourquoi : les badges se dessinent EN DIRECT depuis le compte (nom, photo…) ;
 * sans gel, une correction de profil après la clôture retouchait les badges d'un
 * événement terminé - d'où des badges erronés et une identité incohérente d'un
 * document à l'autre. L'instantané fige ce que la personne était au moment où le
 * dossier a été clos. (Les attestations figent leur identité à l'émission ; le
 * livre des résumés lit les auteurs saisis à la soumission : eux n'en dépendent
 * pas.)
 *
 * ⚠️ Écrase un instantané existant : une seconde archivage REFLÈTE les
 * corrections faites entre-temps (désarchiver → corriger → ré-archiver), au lieu
 * de garder une version périmée.
 */
class FreezeEventIdentities
{
    /** @return int Nombre de participants gelés. */
    public function handle(Event $event): int
    {
        $count = 0;

        Participant::where('event_id', $event->id)->with('user')
            ->chunkById(200, function ($participants) use (&$count, $event) {
                foreach ($participants as $participant) {
                    $this->freeze($event, $participant);
                    $count++;
                }
            });

        return $count;
    }

    private function freeze(Event $event, Participant $participant): void
    {
        $identity = $participant->liveIdentity();

        $identity['photo_path'] = $this->freezePhoto($event, $participant, (string) $identity['photo_path']);
        $identity['frozen_at'] = now()->toIso8601String();
        // L'URL est recalculée à la lecture depuis photo_path : on ne stocke pas
        // d'URL absolue (elle se périmerait si le domaine changeait).
        unset($identity['photo']);

        $meta = $participant->meta ?? [];
        $meta['identity'] = $identity;
        $participant->update(['meta' => $meta]);
    }

    /**
     * Recopie la photo de profil dans un emplacement FIGÉ, propre à l'événement :
     * sans copie, une suppression ou un remplacement ultérieur de la photo de
     * profil casserait le badge gelé. Renvoie le chemin figé (ou '' si aucune).
     */
    private function freezePhoto(Event $event, Participant $participant, string $sourcePath): string
    {
        if ($sourcePath === '' || ! Storage::disk('public')->exists($sourcePath)) {
            return '';
        }

        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $frozen = "event-identity/{$event->id}/{$participant->id}.{$ext}";

        // copy() écrase la cible : un ré-archivage reprend la photo courante.
        Storage::disk('public')->delete($frozen);
        Storage::disk('public')->copy($sourcePath, $frozen);

        return $frozen;
    }
}
