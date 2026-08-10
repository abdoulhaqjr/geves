<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Soumission d'un résumé : titre, co-auteurs (JSON), axe, type, statut du
 * workflow de relecture, versionnage (resoumission).
 */
class Submission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** Libellés des statuts du workflow de relecture. */
    public const STATUSES = [
        'submitted' => 'Soumise',
        'under_review' => 'En relecture',
        'revision' => 'Correction demandée',
        'accepted' => 'Acceptée',
        'rejected' => 'Refusée',
    ];

    /** Variante de pastille (.ukc-pill--*) par statut. */
    public const STATUS_PILLS = [
        'submitted' => 'info',
        'under_review' => 'warn',
        'revision' => 'warn',
        'accepted' => 'ok',
        'rejected' => 'err',
    ];

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'meta' => 'array',
            'revision_of' => 'integer',
            'version' => 'integer',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusPill(): string
    {
        return self::STATUS_PILLS[$this->status] ?? 'muted';
    }

    /** Nom de l'auteur correspondant (1er des authors). */
    public function correspondingAuthorName(): ?string
    {
        return $this->authors[0]['name'] ?? $this->participant?->full_name;
    }

    /** E-mail de l'auteur correspondant (pour les notifications). */
    public function correspondingAuthorEmail(): ?string
    {
        return $this->authors[0]['email'] ?? $this->participant?->email;
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Versions antérieures archivées lors des resoumissions. */
    public function versions(): HasMany
    {
        return $this->hasMany(Submission::class, 'revision_of')->latest('version');
    }

    /** Ne garde que les soumissions canoniques (pas les archives de version). */
    public function scopeCanonical($query)
    {
        return $query->where('revision_of', 0);
    }

    /** L'auteur peut resoumettre uniquement quand une correction est demandée. */
    public function canBeRevised(): bool
    {
        return $this->status === 'revision' && (int) $this->revision_of === 0;
    }
}
