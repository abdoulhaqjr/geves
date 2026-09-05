<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Média d'une édition : photo de galerie, vidéo, livret ou document.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    /** Types de média d'une édition (documents exposés aux participants). */
    public const TYPES = [
        'document' => 'Document',
        'livret' => 'Livret',
        'video' => 'Vidéo',
        'photo' => 'Photo',
        'link' => 'Lien',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
