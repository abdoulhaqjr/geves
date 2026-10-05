<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Jalon du projet d'un événement : une échéance clé du cycle de vie du colloque
 * (ouverture de l'appel, clôture des soumissions, notifications, tenue, actes…).
 *
 * @property int $id
 * @property int $event_id
 * @property string $title
 * @property string|null $description
 * @property Carbon|null $due_date
 * @property string $status
 * @property int $position
 * @property Carbon|null $done_at
 * @property Event|null $event
 */
class ProjectMilestone extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public const STATUSES = [
        'pending' => 'À venir',
        'done' => 'Atteint',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'done_at' => 'datetime',
            'position' => 'integer',
        ];
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    /** En retard : échéance dépassée et jalon non atteint. */
    public function isOverdue(): bool
    {
        return $this->due_date !== null && ! $this->isDone() && $this->due_date->isPast();
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
