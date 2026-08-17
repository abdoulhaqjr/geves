<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Observers\SubmissionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Soumission d'un résumé : titre, co-auteurs (JSON), axe, type, statut du
 * workflow de relecture, versionnage (resoumission). Statuts : {@see SubmissionStatus}.
 */
#[ObservedBy([SubmissionObserver::class])]
class Submission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** @return array<string, string> valeur => libellé (filtres/affichage). */
    public static function statuses(): array
    {
        return SubmissionStatus::options();
    }

    /** @return array<string, string> valeur => variante de pastille. */
    public static function statusPills(): array
    {
        return SubmissionStatus::pills();
    }

    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'meta' => 'array',
            'revision_of' => 'integer',
            'version' => 'integer',
        ];
    }

    /** Statut typé (ou null si valeur inconnue). */
    public function statusEnum(): ?SubmissionStatus
    {
        return SubmissionStatus::tryFrom((string) $this->status);
    }

    public function statusLabel(): string
    {
        return $this->statusEnum()?->label() ?? (string) $this->status;
    }

    public function statusPill(): string
    {
        return $this->statusEnum()?->pill() ?? 'muted';
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

    /** @return BelongsTo<Participant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Versions antérieures archivées lors des resoumissions.
     *
     * @return HasMany<Submission, $this>
     */
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
