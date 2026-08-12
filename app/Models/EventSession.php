<?php

namespace App\Models;

use App\Enums\MeetingRole;
use App\Enums\MeetingStatus;
use App\Enums\MeetingVisibility;
use App\Meetings\MeetingProviderRegistry;
use App\Meetings\Providers\JitsiProvider;
use App\Meetings\Providers\LiveKitProvider;
use App\Support\StreamEmbed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Session de l'événement : d'atelier ou plénière (modérateur + rapporteur).
 * Table `event_sessions` (`sessions` étant réservée par Laravel). Peut porter
 * une **visio interactive** (présentations en ligne) : voir les champs `meeting_*`
 * et les helpers ci-dessous. `meeting_status`/`meeting_visibility` restent des
 * strings en base (l'enum est la source des libellés/pastilles). Les rôles
 * (modérateur/présentateur/participant) sont **calculés**, jamais stockés.
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

    // --- Visio interactive (présentations en ligne) ------------------------

    /** La visio est activée pour cette session (statut ≠ aucune). */
    public function hasMeeting(): bool
    {
        return $this->meetingStatusEnum() !== MeetingStatus::None;
    }

    /** La session est en direct maintenant. */
    public function isLive(): bool
    {
        return $this->meetingStatusEnum() === MeetingStatus::Live;
    }

    /** Un replay est disponible. */
    public function hasRecording(): bool
    {
        return filled($this->recording_url);
    }

    public function meetingStatusEnum(): MeetingStatus
    {
        return MeetingStatus::tryFrom((string) $this->meeting_status) ?? MeetingStatus::None;
    }

    public function meetingVisibilityEnum(): MeetingVisibility
    {
        return MeetingVisibility::tryFrom((string) $this->meeting_visibility) ?? MeetingVisibility::Participants;
    }

    /**
     * Nom de salle. Utilise `meeting_room` si fourni, sinon un nom déterministe
     * et stable dérivé de l'édition et de la session (préfixe configurable).
     */
    public function meetingRoom(): string
    {
        if (filled($this->meeting_room)) {
            return (string) $this->meeting_room;
        }

        $prefix = (string) config('meetings.jitsi.room_prefix', 'gesco');

        return Str::slug($prefix.'-'.$this->edition_id.'-'.$this->id);
    }

    /** URL embarquable du replay, ou null. */
    public function recordingEmbedUrl(): ?string
    {
        return StreamEmbed::toEmbedUrl($this->recording_url);
    }

    /**
     * Identifiants (account_id) des communicants dont la communication est
     * programmée dans cette session (via les créneaux) — les présentateurs.
     *
     * @return array<int, int>
     */
    public function presenterAccountIds(): array
    {
        $submissionIds = $this->slots()->where('submission_id', '>', 0)->pluck('submission_id');

        return Submission::whereIn('id', $submissionIds)
            ->with('participant')
            ->get()
            ->map(fn (Submission $s) => (int) $s->participant?->account_id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Rôle calculé d'un utilisateur dans la visio de cette session.
     * Staff → modérateur ; e-mail = modérateur assigné → modérateur ;
     * communicant programmé → présentateur ; inscrit (ou public) → participant ;
     * sinon accès refusé. (Gating plus fin, ex. payés, à décider avec le mockup.)
     */
    public function meetingRoleFor(?Account $account, bool $isStaff = false): MeetingRole
    {
        if ($isStaff) {
            return MeetingRole::Moderator;
        }

        $public = $this->meetingVisibilityEnum() === MeetingVisibility::Public;

        if (! $account) {
            return $public ? MeetingRole::Attendee : MeetingRole::None;
        }

        if (filled($this->moderator_email)
            && mb_strtolower((string) $this->moderator_email) === mb_strtolower((string) $account->email)) {
            return MeetingRole::Moderator;
        }

        if (in_array($account->id, $this->presenterAccountIds(), true)) {
            return MeetingRole::Presenter;
        }

        $isParticipant = Participant::where('edition_id', $this->edition_id)
            ->where('account_id', $account->id)
            ->exists();

        if ($public || $isParticipant) {
            return MeetingRole::Attendee;
        }

        return MeetingRole::None;
    }

    /** Cet utilisateur peut-il rejoindre la visio ? */
    public function canJoin(?Account $account, bool $isStaff = false): bool
    {
        return $this->meetingRoleFor($account, $isStaff)->canJoin();
    }

    /** URL de connexion à la salle pour cet utilisateur (ou null si refusé/absente). */
    public function joinUrl(?Account $account, bool $isStaff = false, ?string $displayName = null): ?string
    {
        $role = $this->meetingRoleFor($account, $isStaff);

        if (! $role->canJoin()) {
            return null;
        }

        $name = $displayName ?: ($isStaff ? 'Organisation' : $account?->full_name);

        return app(MeetingProviderRegistry::class)->for($this)->joinUrl($this, $role, $name) ?: null;
    }

    /** La salle du fournisseur de cette session peut-elle être embarquée en iframe ? */
    public function meetingEmbeddable(): bool
    {
        return app(MeetingProviderRegistry::class)->for($this)->embeddable();
    }

    /**
     * Config pour l'IFrame API Jitsi (JitsiMeetExternalAPI), ou null si le
     * fournisseur n'est pas Jitsi / accès refusé.
     *
     * @return array{domain: string, roomName: string, jwt: ?string, userName: string}|null
     */
    public function meetingEmbedConfig(?Account $account, bool $isStaff = false, ?string $displayName = null): ?array
    {
        $role = $this->meetingRoleFor($account, $isStaff);

        if (! $role->canJoin()) {
            return null;
        }

        $provider = app(MeetingProviderRegistry::class)->for($this);

        if (! $provider instanceof JitsiProvider) {
            return null;
        }

        $name = $displayName ?: ($isStaff ? 'Organisation' : $account?->full_name);

        return $provider->embedConfig($this, $role, $name);
    }

    /**
     * Config pour le lecteur LiveKit inline (SDK livekit-client), ou null si le
     * fournisseur n'est pas LiveKit / accès refusé. Le token porte les droits
     * (publier/s'abonner/admin) dérivés du rôle.
     *
     * @return array{url: string, token: string, userName: string, canPublish: bool}|null
     */
    public function liveKitEmbedConfig(?Account $account, bool $isStaff = false, ?string $displayName = null): ?array
    {
        $role = $this->meetingRoleFor($account, $isStaff);

        if (! $role->canJoin()) {
            return null;
        }

        $provider = app(MeetingProviderRegistry::class)->for($this);

        if (! $provider instanceof LiveKitProvider) {
            return null;
        }

        $name = $displayName ?: ($isStaff ? 'Organisation' : $account?->full_name);

        return [
            'url' => (string) config('meetings.livekit.url'),
            'token' => $provider->token($this, $role, $name),
            'userName' => $name ?: 'Invité',
            'canPublish' => in_array($role, [MeetingRole::Moderator, MeetingRole::Presenter], true),
        ];
    }

    /** Sessions actuellement en direct. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('meeting_status', MeetingStatus::Live->value);
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
