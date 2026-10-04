<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Présence d'un participant à une séance, pointée par le modérateur ou le
 * rapporteur pendant la séance.
 *
 * ⚠️ **L'absence de ligne n'est pas une absence** : c'est « non pointé ». Un
 * absent constaté porte `status = absent`. Confondre les deux ferait passer pour
 * absents tous ceux qu'on n'a pas eu le temps d'appeler.
 *
 * @property Carbon|null $marked_at
 * @property EventSession|null $session
 * @property Participant|null $participant
 */
class SessionAttendance extends Model
{
    protected $guarded = ['id'];

    /**
     * Verdicts possibles, et leurs libellés.
     *
     * ⚠️ **Deux verdicts, pas trois.** « Excusé » a existé et a été retiré : il
     * ne changeait rien en aval - seul `present` compte dans le PV et dans
     * l'attestation - mais il ajoutait un troisième bouton à trancher pendant
     * qu'on appelle des gens à la voix. Un motif d'absence se dit dans le compte
     * rendu, pas dans une colonne qui ne sert à aucun calcul.
     *
     * ⚠️ Source unique : les deux contrôleurs valident sur `array_keys()` de
     * cette liste. Y remettre une valeur l'ouvre PARTOUT, y compris dans la
     * salle de visio.
     */
    public const STATUSES = [
        'present' => 'Présent',
        'absent' => 'Absent',
    ];

    /** Pointage posé à la main, ou déduit d'une entrée dans la visio. */
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_MEETING = 'meeting';

    /** Posé automatiquement par l'entrée en visio (et non par une personne). */
    public function isAutomatic(): bool
    {
        return $this->source === self::SOURCE_MEETING;
    }

    protected function casts(): array
    {
        return ['marked_at' => 'datetime'];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? (string) $this->status;
    }

    /** @return BelongsTo<EventSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }
}
