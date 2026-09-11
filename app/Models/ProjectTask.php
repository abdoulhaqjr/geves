<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Tâche du projet d'une édition (colonne kanban : à faire / en cours / en
 * revue / terminé). Reliée à l'édition et, éventuellement, à un responsable
 * (membre de l'équipe) et à une phase du cycle de vie du colloque.
 *
 * @property int $id
 * @property int $edition_id
 * @property string $title
 * @property string|null $description
 * @property array<int, array{text: string, done: bool}>|null $checklist
 * @property string $status
 * @property string $priority
 * @property string|null $phase
 * @property string|null $link_key
 * @property int|null $assignee_id
 * @property int|null $created_by
 * @property Carbon|null $due_date
 * @property int $position
 * @property Carbon|null $completed_at
 * @property Edition|null $edition
 * @property User|null $assignee
 */
class ProjectTask extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** Colonnes du tableau kanban (ordre d'affichage). */
    public const STATUSES = [
        'todo' => 'À faire',
        'doing' => 'En cours',
        'review' => 'En revue',
        'done' => 'Terminé',
    ];

    /** Niveaux de priorité (du plus faible au plus fort). */
    public const PRIORITIES = [
        'low' => 'Basse',
        'normal' => 'Normale',
        'high' => 'Haute',
        'urgent' => 'Urgente',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'position' => 'integer',
            'checklist' => 'array',
        ];
    }

    /**
     * Avancement de la checklist : [éléments cochés, total].
     *
     * @return array{0: int, 1: int}
     */
    public function checklistProgress(): array
    {
        $items = is_array($this->checklist) ? $this->checklist : [];
        $done = count(array_filter($items, fn ($i) => ! empty($i['done'])));

        return [$done, count($items)];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function priorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    /** En retard : échéance dépassée et tâche non terminée. */
    public function isOverdue(): bool
    {
        return $this->due_date !== null && ! $this->isDone() && $this->due_date->isPast();
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /** @return HasMany<ProjectTaskComment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(ProjectTaskComment::class, 'task_id');
    }
}
