<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/** Guide éditable affiché dans les espaces d'administration et participant. */
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

    public function videoUrl(): ?string
    {
        if (filled($this->video_path)) {
            return Storage::disk('public')->url($this->video_path);
        }

        return $this->video_url;
    }
}
