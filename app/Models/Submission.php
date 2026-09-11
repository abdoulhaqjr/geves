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
 *
 * @property array<int, array<string, mixed>>|null $authors
 * @property array<string, mixed>|null $meta
 * @property Edition|null $edition
 * @property Participant|null $participant
 * @property float|null $reviews_avg_score agrégat withAvg('reviews', 'score')
 */
#[ObservedBy([SubmissionObserver::class])]
class Submission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** Points à confirmer par l'auteur au dépôt de la version définitive (camera-ready). */
    public const CAMERA_READY_CHECKLIST = [
        'template' => 'Le document respecte le gabarit officiel (format, marges, police).',
        'final_authors' => 'La liste et l’ordre des auteurs sont définitifs.',
        'clean' => 'Le document ne contient ni commentaires ni marques de révision.',
        'final_content' => 'Le titre et le résumé correspondent à la version finale.',
    ];

    /** @return array<string, string> valeur => libellé (filtres/affichage). */
    public static function statuses(): array
    {
        return SubmissionStatus::options();
    }

    /**
     * Version définitive (camera-ready) déposée par l'auteur, ou null.
     *
     * @return array<string, mixed>|null
     */
    public function cameraReady(): ?array
    {
        $cr = $this->meta['camera_ready'] ?? null;

        return is_array($cr) ? $cr : null;
    }

    public function hasCameraReady(): bool
    {
        return $this->cameraReady() !== null;
    }

    /**
     * Phase de réponse aux relecteurs (rebuttal), ou null.
     *
     * @return array<string, mixed>|null
     */
    public function rebuttal(): ?array
    {
        $r = $this->meta['rebuttal'] ?? null;

        return is_array($r) ? $r : null;
    }

    /** La phase de réponse est-elle ouverte pour cette communication ? */
    public function rebuttalOpen(): bool
    {
        return ! empty($this->rebuttal()['opened_at']);
    }

    /** L'auteur a-t-il déposé sa réponse ? */
    public function hasRebuttal(): bool
    {
        return filled($this->rebuttal()['text'] ?? null);
    }

    public function rebuttalText(): ?string
    {
        $t = $this->rebuttal()['text'] ?? null;

        return filled($t) ? (string) $t : null;
    }

    /** @return array<string, string> valeur => variante de pastille. */
    public static function statusPills(): array
    {
        return SubmissionStatus::pills();
    }

    /** Communications originales (non révisions) déjà déposées par un compte sur une édition. */
    public static function usedCountFor(Edition $edition, User $account): int
    {
        return self::where('edition_id', $edition->id)
            ->where('revision_of', 0)
            ->whereHas('participant', fn ($q) => $q->where('user_id', $account->id))
            ->count();
    }

    /** Communications encore déposables par ce compte sur cette édition (0 = quota atteint). */
    public static function remainingFor(Edition $edition, User $account): int
    {
        $max = (int) ($edition->settings['max_communications'] ?? 3);

        return max(0, $max - self::usedCountFor($edition, $account));
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

    /** @return BelongsTo<Edition, $this> */
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
