<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Document de travail d'une tâche de projet, rédigé à plusieurs en direct.
 *
 * @property int $id
 * @property int $task_id
 * @property string $title
 * @property string|null $html
 * @property string|null $state
 * @property int $revision
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $saved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property ProjectTask|null $task
 * @property User|null $author
 * @property User|null $editor
 */
class ProjectTaskDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Délai au-delà duquel un enregistrement ouvre une nouvelle version.
     *
     * ⚠️ L'enregistrement est AUTOMATIQUE et fréquent (quelques secondes après
     * la dernière frappe) : verser une version à chaque fois rendrait
     * l'historique illisible - des centaines d'entrées pour une après-midi de
     * rédaction, et plus aucun point de repère où revenir.
     */
    public const VERSION_EVERY_MINUTES = 10;

    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'saved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProjectTask, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<ProjectTaskDocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(ProjectTaskDocumentVersion::class, 'document_id');
    }

    /**
     * Qui peut ÉCRIRE dans ce document.
     *
     * ⚠️ **L'écriture suit l'association à la tâche, pas l'accès au module.**
     * Tout le staff qui ouvre le Projet d'un événement peut LIRE le document -
     * c'est le propre d'un plan de travail partagé - mais rédiger dedans est
     * réservé aux personnes que la tâche désigne : son responsable et ses
     * collaborateurs. Sans cette distinction, « associer quelqu'un à la tâche »
     * ne voudrait rien dire, puisque tout le monde écrirait déjà.
     *
     * ⚠️ **L'ADMINISTRATEUR fait exception, et c'est bien lui - pas le seul super
     * administrateur.** Le modèle du projet lui donne accès à TOUT ; écrire ici
     * `isColloqueSuperAdmin()` refermerait ce qu'on vient de lui ouvrir, et il
     * est le recours quand une équipe est bloquée sur son propre document.
     */
    public function isEditableBy(?User $user): bool
    {
        // ⚠️ L'accès au module passe EN PREMIER : quelqu'un qu'on a retiré de
        // l'équipe de l'événement ne doit plus écrire, même si son nom est resté
        // dans les collaborateurs d'une vieille tâche.
        if (! $this->isVisibleTo($user) || ! $user instanceof User) {
            return false;
        }

        if ($user->isColloqueAdmin()) {
            return true;
        }

        return (int) $this->created_by === (int) $user->id
            || ($this->task?->isAssociate($user) ?? false);
    }

    /**
     * Qui peut LIRE ce document : quiconque ouvre le module Projet de
     * l'événement. Un plan de travail partagé se lit par l'équipe ; seule la
     * rédaction se restreint (cf. {@see self::isEditableBy()}).
     *
     * ⚠️ Même règle pour la page ET pour l'autorisation du canal temps réel
     * (`routes/channels.php`) : une page gardée dont le canal ne l'est pas
     * laisserait écouter les frappes depuis la console du navigateur.
     */
    public function isVisibleTo(?User $user): bool
    {
        $eventId = $this->task?->event_id;

        return $user !== null
            && $eventId !== null
            && $user->canOnEdition('project', (int) $eventId);
    }

    /**
     * Une nouvelle version doit-elle être versée pour cet enregistrement ?
     *
     * Vrai au tout premier enregistrement, puis seulement quand la dernière
     * version remonte à plus de {@see self::VERSION_EVERY_MINUTES}.
     */
    public function shouldOpenVersion(): bool
    {
        $derniere = $this->versions()->latest('id')->first();

        return $derniere === null
            || $derniere->created_at === null
            || $derniere->created_at->diffInMinutes(now()) >= self::VERSION_EVERY_MINUTES;
    }
}
