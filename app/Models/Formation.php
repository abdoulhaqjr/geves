<?php

namespace App\Models;

use App\Enums\MeetingRole;
use App\Enums\MeetingStatus;
use App\Enums\MeetingVisibility;
use App\Meetings\Contracts\Meetable;
use App\Meetings\MeetingProviderRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Formation proposée dans le cadre d'une édition (module activable). Une édition
 * peut en proposer plusieurs ; chacune a ses formateurs, ses frais (0 = gratuite),
 * son lieu/salle, ses dates et, à terme, sa visio.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Edition|null $edition
 */
class Formation extends Model implements Meetable
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published' => 'boolean',
            'public' => 'boolean',
            'meta' => 'array',
        ];
    }

    /** Formation gratuite (aucun frais) : inscription sans paiement. */
    public function isFree(): bool
    {
        return (float) $this->fee <= 0.0;
    }

    /** URL publique du flyer/bannière téléversé, ou null. */
    public function flyerUrl(): ?string
    {
        return filled($this->flyer_path)
            ? Storage::disk('public')->url((string) $this->flyer_path)
            : null;
    }

    /* ----------------------------- Visio (Meetable) ----------------------------- */

    public function meetingStatusEnum(): MeetingStatus
    {
        return MeetingStatus::tryFrom((string) $this->meeting_status) ?? MeetingStatus::None;
    }

    public function meetingVisibilityEnum(): MeetingVisibility
    {
        return MeetingVisibility::tryFrom((string) $this->meeting_visibility) ?? MeetingVisibility::Participants;
    }

    public function hasMeeting(): bool
    {
        return $this->meetingStatusEnum() !== MeetingStatus::None;
    }

    public function isLive(): bool
    {
        return $this->meetingStatusEnum() === MeetingStatus::Live;
    }

    public function meetingRoom(): string
    {
        if (filled($this->meeting_room)) {
            return (string) $this->meeting_room;
        }

        $prefix = (string) config('meetings.jitsi.room_prefix', 'gesco');

        return Str::slug($prefix.'-formation-'.$this->edition_id.'-'.$this->id);
    }

    public function meetingUrl(): ?string
    {
        return $this->meeting_url;
    }

    public function meetingProviderKey(): ?string
    {
        return $this->meeting_provider;
    }

    /**
     * Rôle visio : staff & formateurs = modérateur ; inscrit éligible (réglé, ou
     * inscrit si gratuite) = participant ; sinon aucun. Pas d'accès public : la
     * visio est réservée aux inscrits et aux formateurs.
     */
    public function meetingRoleFor(?User $account, bool $isStaff = false): MeetingRole
    {
        if ($isStaff) {
            return MeetingRole::Moderator;
        }
        if ($account === null) {
            return MeetingRole::None;
        }

        $isTrainer = $this->trainers()
            ->where(fn ($q) => $q->where('user_id', $account->id)
                ->orWhereRaw('LOWER(email) = ?', [mb_strtolower((string) $account->email)]))
            ->exists();
        if ($isTrainer) {
            return MeetingRole::Moderator;
        }

        $eligible = $this->enrollments()
            ->where('user_id', $account->id)
            ->where(fn ($q) => $q->where('status', 'paid')
                ->when($this->isFree(), fn ($q2) => $q2->orWhere('status', 'registered')))
            ->exists();

        return $eligible ? MeetingRole::Attendee : MeetingRole::None;
    }

    public function canJoin(?User $account, bool $isStaff = false): bool
    {
        return $this->meetingRoleFor($account, $isStaff)->canJoin();
    }

    public function joinUrl(?User $account, bool $isStaff = false, ?string $displayName = null): ?string
    {
        $role = $this->meetingRoleFor($account, $isStaff);
        if (! $role->canJoin()) {
            return null;
        }

        $name = $displayName ?: ($isStaff ? 'Organisation' : $account?->name);

        return app(MeetingProviderRegistry::class)->for($this)->joinUrl($this, $role, $name) ?: null;
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return HasMany<FormationTrainer, $this> */
    public function trainers(): HasMany
    {
        return $this->hasMany(FormationTrainer::class);
    }

    /** @return HasMany<FormationEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(FormationEnrollment::class);
    }
}
