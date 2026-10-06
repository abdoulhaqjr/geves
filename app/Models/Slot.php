<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Créneau de passage : moment de présentation (salle, horaire) rattaché à une
 * communication acceptée.
 *
 * @property int $id
 * @property int $event_id
 * @property int $submission_id
 * @property int $participant_id
 * @property int|null $session_id
 * @property string|null $session_label
 * @property string|null $room
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $chosen_by_participant
 * @property Carbon|null $presented_at
 * @property int|null $presented_by
 * @property array<array-key, mixed>|null $schedule_log
 */
class Slot extends Model
{
    protected $guarded = ['id'];

    /** Colonnes dont le changement, sur un créneau déjà placé, est une reprogrammation. */
    private const SCHEDULE_COLS = ['starts_at', 'ends_at', 'room', 'session_id', 'session_label'];

    /** Nombre maximal d'entrées conservées dans le journal (les plus récentes). */
    private const LOG_MAX = 10;

    /**
     * Drapeau TRANSITOIRE (non persisté) : vrai quand l'enregistrement courant a
     * produit une entrée de reprogrammation. Il permet à l'appelant (un contrôleur
     * déclenché par un humain) de décider d'envoyer une notification SANS que
     * l'évènement du modèle ne la déclenche lui-même - sinon un seed de volume ou un
     * rejeu idempotent enverrait des centaines de messages (cf. piège documenté).
     */
    public bool $rescheduledNow = false;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'chosen_by_participant' => 'boolean',
            'presented_at' => 'datetime',
            'schedule_log' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Capture AUTOMATIQUE d'une reprogrammation : dès qu'un créneau portant une
        // communication (`submission_id > 0`) et DÉJÀ horodaté voit son horaire, sa
        // salle ou sa séance changer, on consigne l'avant/après. On s'appuie sur
        // l'évènement `updated` (après écriture) plutôt que sur les contrôleurs : TOUTE
        // modification de créneau est ainsi tracée (modif manuelle, glisser-déposer,
        // affectation auto), sans instrumenter chaque chemin. ⚠️ Un placement INITIAL
        // (pas d'horaire précédent) n'est pas une reprogrammation : on l'ignore.
        static::updated(function (Slot $slot): void {
            if ((int) $slot->submission_id <= 0) {
                return;
            }
            if (! $slot->wasChanged(self::SCHEDULE_COLS)) {
                return;
            }
            if ($slot->getOriginal('starts_at') === null) {
                return; // première planification, pas un déplacement
            }

            $log = $slot->schedule_log ?? [];
            $log[] = [
                'at' => now()->toIso8601String(),
                'from' => [
                    'starts_at' => self::iso($slot->getOriginal('starts_at')),
                    'ends_at' => self::iso($slot->getOriginal('ends_at')),
                    'room' => $slot->getOriginal('room'),
                    'session_label' => $slot->getOriginal('session_label'),
                ],
                'to' => [
                    'starts_at' => self::iso($slot->starts_at),
                    'ends_at' => self::iso($slot->ends_at),
                    'room' => $slot->room,
                    'session_label' => $slot->session_label,
                ],
            ];
            // Écriture silencieuse : ne pas relancer l'évènement `updated` (et de toute
            // façon `schedule_log` n'est pas une colonne suivie, donc pas de récursion).
            $slot->schedule_log = array_slice($log, -self::LOG_MAX);
            $slot->saveQuietly();
            $slot->rescheduledNow = true;
        });
    }

    /** Normalise une valeur d'horaire (Carbon|string|null) en ISO-8601, ou null. */
    private static function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Ce créneau a-t-il été reprogrammé au moins une fois ? */
    public function wasRescheduled(): bool
    {
        return ! empty($this->schedule_log);
    }

    /**
     * Dernière reprogrammation consignée (avant/après), ou null.
     *
     * @return array<string, mixed>|null
     */
    public function lastReschedule(): ?array
    {
        $log = $this->schedule_log ?? [];
        if ($log === []) {
            return null;
        }
        $last = end($log);

        return is_array($last) ? $last : null;
    }

    /**
     * Le passage a-t-il été constaté par qui tenait la séance ?
     *
     * ⚠️ C'est ce constat qui ouvre les attestations des auteurs : il ne se
     * déduit pas de l'horaire passé. Un créneau peut être dépassé sans que la
     * communication ait eu lieu - l'auteur ne s'est pas connecté, la séance a
     * pris du retard, le sujet a été reporté.
     */
    public function wasPresented(): bool
    {
        return $this->presented_at !== null;
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<EventSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    /** @return BelongsTo<Submission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
