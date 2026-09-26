<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Formateur d'une formation. Lié au compte unifié (`users`) s'il en a un (par
 * e-mail), sinon saisie libre (nom/e-mail/affiliation).
 *
 * @property array<string, mixed>|null $meta
 * @property Formation|null $formation
 * @property User|null $user
 */
class FormationTrainer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_moderator' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * URL de la photo : photo téléversée du formateur, sinon photo du compte lié
     * (si l'e-mail correspond à un utilisateur), sinon null (avatar par défaut).
     */
    public function photoUrl(): ?string
    {
        if (filled($this->photo_path)) {
            return Storage::disk('public')->url((string) $this->photo_path);
        }
        if ($this->user && filled($this->user->photo_path)) {
            return Storage::url((string) $this->user->photo_path);
        }

        return null;
    }

    /** URL de la signature téléversée (apposée sur les attestations), ou null. */
    public function signatureUrl(): ?string
    {
        return filled($this->signature_path)
            ? Storage::disk('public')->url((string) $this->signature_path)
            : null;
    }

    /** Nom complet affiché sous la signature (grade + nom). */
    public function displayName(): string
    {
        return trim(($this->title ? $this->title.' ' : '').trim((string) $this->name));
    }

    /** Initiale pour l'avatar par défaut. */
    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim((string) $this->name), 0, 1)) ?: '?';
    }

    /** @return BelongsTo<Formation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(Formation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
