<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Guide éditable affiché dans les espaces d'administration et participant.
 *
 * ⚠️ **Les colonnes castées déclarent leur type ICI** (même convention que
 * `TutorialVideoRequest` et `Attestation`) : sans annotation, l'analyse lit le
 * SCHÉMA - une colonne `json` est un `text`, donc une chaîne. Elle croyait alors
 * comparer une chaîne à un tableau dans `OnboardingController` (« comparaison
 * toujours vraie ») et ne pouvait plus dire si la détection de guide modifié
 * était juste. Le cast et l'annotation vont par paire.
 *
 * @property array<int, string> $steps
 * @property array<int, string>|null $participant_roles
 * @property bool $video_visible_to_participants
 */
class TutorialGuide extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'participant_roles' => 'array',
            'video_visible_to_participants' => 'boolean',
        ];
    }

    /** @return HasMany<TutorialVideoRequest, $this> */
    public function videoRequests(): HasMany
    {
        return $this->hasMany(TutorialVideoRequest::class);
    }

    public function hasVideo(): bool
    {
        return filled($this->video_path) || filled($this->video_url);
    }

    public function hasCurrentVideo(): bool
    {
        return $this->hasVideo() && $this->video_generation_status !== 'stale';
    }

    public function videoUrl(): ?string
    {
        if (filled($this->video_path)) {
            return Storage::disk('public')->url($this->video_path);
        }

        return $this->video_url;
    }
}
