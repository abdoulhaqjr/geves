<?php

namespace App\Support;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\Slot;
use Illuminate\Support\Collection;

/**
 * Les ESPACES d'un événement (amphis, salles, hall posters) et leur lien avec le
 * programme.
 *
 * ⚠️⚠️ **LE NOM EST LE SEUL LIEN.** Une séance et un créneau ne portent pas
 * l'identifiant d'un espace : ils portent son **nom**, en texte libre
 * (`event_sessions.room`, `slots.room`, et la carte `atelier_rooms` de la config).
 * Deux conséquences, et ce sont elles qui justifient cette classe :
 *
 * - une salle saisie sur une séance **existe** pour le programme sans être
 *   déclarée nulle part - elle est donc **déclarée d'office** ({@see missing()}),
 *   sans quoi la gestion des espaces montrerait une liste vide à côté d'un
 *   programme qui tourne dans neuf salles ;
 * - renommer un espace, c'est **renommer la salle partout** ({@see rename()}).
 *   Changer la seule ligne de configuration laisserait les séances sur l'ancien
 *   nom : l'espace renommé deviendrait un espace de plus, vide, et le programme
 *   continuerait d'annoncer un nom qui n'est plus déclaré.
 */
final class ProgramSpaces
{
    /**
     * Les salles que le programme emploie vraiment, triées.
     *
     * @param  Collection<int, EventSession>  $sessions
     * @param  Collection<int, Slot>  $slots
     * @param  array<array-key, string|null>  $atelierRooms
     * @return list<string>
     */
    public static function used(Collection $sessions, Collection $slots, array $atelierRooms = []): array
    {
        return $sessions->pluck('room')
            ->merge($slots->pluck('room'))
            ->merge(array_values($atelierRooms))
            ->map(fn ($room) => trim((string) $room))
            ->filter()->unique()->sort()->values()->all();
    }

    /**
     * Les salles employées par le programme qu'aucun espace ne déclare.
     *
     * @param  array<int, array<string, mixed>>  $spaces
     * @param  list<string>  $used
     * @return list<string>
     */
    public static function missing(array $spaces, array $used): array
    {
        $declared = array_map(fn (array $space) => trim((string) ($space['name'] ?? '')), $spaces);

        return array_values(array_filter($used, fn (string $room) => ! in_array($room, $declared, true)));
    }

    /**
     * Ce que chaque salle porte, pour que la fiche d'un espace dise ce qu'il
     * accueille - et qu'on ne le supprime pas par mégarde.
     *
     * @param  Collection<int, EventSession>  $sessions
     * @param  Collection<int, Slot>  $slots
     * @param  array<array-key, string|null>  $atelierRooms
     * @return array<string, array{sessions: int, slots: int, ateliers: int}>
     */
    public static function usage(Collection $sessions, Collection $slots, array $atelierRooms = []): array
    {
        $usage = [];
        $compte = function (string $room, string $quoi) use (&$usage): void {
            $room = trim($room);
            if ($room === '') {
                return;
            }
            $usage[$room] ??= ['sessions' => 0, 'slots' => 0, 'ateliers' => 0];
            $usage[$room][$quoi]++;
        };

        $sessions->each(fn ($s) => $compte((string) $s->room, 'sessions'));
        $slots->each(fn ($sl) => $compte((string) $sl->room, 'slots'));
        foreach ($atelierRooms as $room) {
            $compte((string) $room, 'ateliers');
        }

        return $usage;
    }

    /**
     * Renomme une salle PARTOUT où le programme la nomme : séances, créneaux et
     * ateliers. Rend ce qui a été réécrit, pour le dire à l'écran.
     *
     * ⚠️ La réécriture passe par le **query builder**, donc elle ne déclenche pas
     * l'évènement `Slot::updated` et n'inscrit rien au journal de reprogrammation -
     * **et c'est voulu** : renommer une salle ne déplace personne. L'assistance ne
     * doit pas annoncer à deux cents auteurs un changement de lieu qui n'a pas eu
     * lieu.
     *
     * @param  array<array-key, string|null>  $atelierRooms
     * @return array{sessions: int, slots: int, ateliers: array<array-key, string>}
     */
    public static function rename(Event $event, string $from, string $to, array $atelierRooms = []): array
    {
        $sessions = $event->sessions()->where('room', $from)->update(['room' => $to]);
        $slots = $event->slots()->where('room', $from)->update(['room' => $to]);

        $ateliers = [];
        foreach ($atelierRooms as $atelierId => $room) {
            if (trim((string) $room) === $from) {
                $ateliers[(string) $atelierId] = $to;
            }
        }

        return ['sessions' => $sessions, 'slots' => $slots, 'ateliers' => $ateliers];
    }
}
