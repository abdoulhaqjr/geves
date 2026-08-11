<?php

namespace App\Models;

use App\Enums\StreamStatus;
use App\Enums\StreamVisibility;
use App\Support\StreamEmbed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Session de l'événement : d'atelier ou plénière (modérateur + rapporteur).
 * Table `event_sessions` (`sessions` étant réservée par Laravel). Peut porter
 * une diffusion en direct (voir les champs `stream_*` et les helpers ci-dessous ;
 * `stream_status`/`stream_visibility` restent des strings en base, comme les
 * autres statuts — l'enum est la source des libellés/pastilles).
 */
class EventSession extends Model
{
    protected $table = 'event_sessions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'session_day' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // --- Diffusion en direct (live streaming) ------------------------------

    /** Une diffusion est configurée (URL renseignée). */
    public function hasStream(): bool
    {
        return filled($this->stream_url);
    }

    /** La session est en direct maintenant. */
    public function isLive(): bool
    {
        return $this->stream_status === StreamStatus::Live->value && $this->hasStream();
    }

    /** Un replay est disponible. */
    public function hasRecording(): bool
    {
        return filled($this->recording_url);
    }

    public function streamStatusEnum(): StreamStatus
    {
        return StreamStatus::tryFrom((string) $this->stream_status) ?? StreamStatus::None;
    }

    public function streamVisibilityEnum(): StreamVisibility
    {
        return StreamVisibility::tryFrom((string) $this->stream_visibility) ?? StreamVisibility::Participants;
    }

    /** URL embarquable (iframe) de la diffusion, ou null. */
    public function streamEmbedUrl(): ?string
    {
        return StreamEmbed::toEmbedUrl($this->stream_url);
    }

    /** URL embarquable du replay, ou null. */
    public function recordingEmbedUrl(): ?string
    {
        return StreamEmbed::toEmbedUrl($this->recording_url);
    }

    /** Fournisseur détecté (youtube | vimeo | custom | null). */
    public function streamProvider(): ?string
    {
        return StreamEmbed::provider($this->stream_url);
    }

    /**
     * Ce compte peut-il regarder la diffusion ? Publique → tout le monde ;
     * participants → le compte doit être inscrit sur l'édition. (Gating plus fin,
     * ex. payés seulement, à décider avec le mockup.)
     */
    public function canBeWatchedBy(?Account $account): bool
    {
        if ($this->streamVisibilityEnum() === StreamVisibility::Public) {
            return true;
        }

        if (! $account) {
            return false;
        }

        return Participant::where('edition_id', $this->edition_id)
            ->where('account_id', $account->id)
            ->exists();
    }

    /** Sessions actuellement en direct. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('stream_status', StreamStatus::Live->value)
            ->whereNotNull('stream_url');
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class, 'session_id');
    }
}
