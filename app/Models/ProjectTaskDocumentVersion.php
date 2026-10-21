<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Version d'un document de tâche : un état daté, attribué à la personne qui
 * tenait le clavier au moment de l'enregistrement.
 *
 * @property int $id
 * @property int $document_id
 * @property int $revision
 * @property string|null $label
 * @property string|null $file_path
 * @property string|null $html
 * @property string|null $state
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property ProjectTaskDocument|null $document
 * @property User|null $author
 */
class ProjectTaskDocumentVersion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }

    /** @return BelongsTo<ProjectTaskDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ProjectTaskDocument::class, 'document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
