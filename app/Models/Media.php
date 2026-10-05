<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Média d'une événement : photo de galerie, vidéo, livret ou document.
 */
class Media extends Model
{
    protected $table = 'media';

    protected $guarded = ['id'];

    /** Types de média d'une événement (documents exposés aux participants). */
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

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
