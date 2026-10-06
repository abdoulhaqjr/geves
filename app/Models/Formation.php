<?php

namespace App\Models;

use App\Enums\MeetingRole;
use App\Enums\MeetingStatus;
use App\Enums\MeetingVisibility;
use App\Meetings\Concerns\JoinsMeetings;
use App\Meetings\Contracts\Meetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Formation proposée dans le cadre d'un événement (module activable). Un événement
 * peut en proposer plusieurs ; chacune a ses formateurs, ses frais (0 = gratuite),
 * son lieu/salle, ses dates et, à terme, sa visio.
 *
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Event|null $event
 */
class Formation extends Model implements Meetable
{
    use JoinsMeetings;

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

    /**
     * Formations publiées d'un événement donné. Une formation appartient toujours
     * à un événement (= l'événement) : l'espace participant se scope sur l'événement
     * sélectionnée globalement, jamais sur « tous les événements ».
     *
     * @param  Builder<Formation>  $query
     * @return Builder<Formation>
     */
    public function scopePublishedIn(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id)->where('published', true);
    }

    /**
     * L'événement propose-t-elle des formations ? (module activé pour l'événement ET
     * au moins une formation publiée). Sert au lien du menu latéral participant
     * ET à la garde de la page : si c'est faux, le lien est masqué et la page
     * n'est pas accessible.
     */
    public static function offeredIn(?Event $event): bool
    {
        return $event !== null
            && $event->formationEnabled()
            && self::query()->publishedIn($event)->exists();
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

    /**
     * Nom technique de la salle, TOUJOURS UNIQUE PAR FORMATION (cf. la note sur
     * {@see EventSession::meetingRoom()}) : `meeting_room` n'est qu'une étiquette,
     * suffixée par l'événement + la formation pour éviter toute collision de salle.
     */
    public function meetingRoom(): string
    {
        $prefix = filled($this->meeting_room)
            ? (string) $this->meeting_room
            : (string) config('meetings.jitsi.room_prefix', 'gesco');

        return Str::slug($prefix.'-formation-'.$this->event_id.'-'.$this->id);
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
     * Ce compte anime-t-il la formation ? (formateur lié par compte ou par e-mail).
     * Un formateur n'a ni à s'inscrire ni à payer : il rejoint en modérateur.
     */
    public function isTrainer(?User $account): bool
    {
        if ($account === null) {
            return false;
        }

        return $this->trainers()
            ->where(fn ($q) => $q->where('user_id', $account->id)
                ->when(filled($account->email), fn ($q2) => $q2->orWhereRaw('LOWER(email) = ?', [mb_strtolower((string) $account->email)])))
            ->exists();
    }

    /**
     * Rôle visio : staff & formateurs = modérateur ; inscrit éligible (réglé, ou
     * inscrit si gratuite) = participant ; sinon aucun. Pas d'accès public : la
     * visio est réservée aux inscrits et aux formateurs.
     *
     * ⚠️ Un inscrit d'une formation **payante** qui n'a pas réglé n'a **aucun**
     * accès à la visio (règle métier explicite).
     */
    /** Événement qui porte les réglages de visio de cette salle (cf. Meetable). */
    public function meetingEdition(): ?Event
    {
        return $this->event;
    }

    public function meetingRoleFor(?User $account, bool $isStaff = false): MeetingRole
    {
        if ($isStaff) {
            return MeetingRole::Moderator;
        }
        if ($account === null) {
            return MeetingRole::None;
        }

        if ($this->isTrainer($account)) {
            return MeetingRole::Moderator;
        }

        $eligible = $this->enrollments()
            ->where('user_id', $account->id)
            ->where(fn ($q) => $q->where('status', 'paid')
                ->when($this->isFree(), fn ($q2) => $q2->orWhere('status', 'registered')))
            ->exists();

        return $eligible ? MeetingRole::Attendee : MeetingRole::None;
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
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
