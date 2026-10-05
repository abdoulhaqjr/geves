<?php

namespace App\Models;

use App\Enums\MeetingRole;
use App\Enums\MeetingStatus;
use App\Enums\MeetingVisibility;
use App\Meetings\Concerns\JoinsMeetings;
use App\Meetings\Contracts\Meetable;
use App\Support\SessionRoster;
use App\Support\StreamEmbed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Session de l'événement : d'atelier ou plénière (modérateur + rapporteur).
 * Table `event_sessions` (`sessions` étant réservée par Laravel). Peut porter
 * une **visio interactive** (présentations en ligne) : voir les champs `meeting_*`
 * et les helpers ci-dessous. `meeting_status`/`meeting_visibility` restent des
 * strings en base (l'enum est la source des libellés/pastilles). Les rôles
 * (modérateur/présentateur/participant) sont **calculés**, jamais stockés.
 *
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $session_day
 * @property Atelier|null $atelier
 * @property Event|null $event
 */
class EventSession extends Model implements Meetable
{
    use JoinsMeetings;

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

    /**
     * La séance a-t-elle commencé (jour + heure atteints) ? Sert à n'ouvrir la
     * rédaction du rapport qu'une fois la séance en cours ou passée. Repli sur le
     * jour seul si l'heure n'est pas renseignée ; false si aucune date.
     */
    public function hasStarted(): bool
    {
        if ($this->starts_at !== null) {
            return $this->starts_at->lte(now());
        }

        if ($this->session_day !== null) {
            return $this->session_day->startOfDay()->lte(now());
        }

        return false;
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
     * et stable dérivé de l'événement et de la session (préfixe configurable).
     */
    public function meetingRoom(): string
    {
        if (filled($this->meeting_room)) {
            return (string) $this->meeting_room;
        }

        $prefix = (string) config('meetings.jitsi.room_prefix', 'gesco');

        return Str::slug($prefix.'-'.$this->event_id.'-'.$this->id);
    }

    /** Lien externe (fournisseur « custom »), pour le contrat Meetable. */
    public function meetingUrl(): ?string
    {
        return $this->meeting_url;
    }

    /** Clé du fournisseur choisi, pour le contrat Meetable. */
    public function meetingProviderKey(): ?string
    {
        return $this->meeting_provider;
    }

    /** URL embarquable du replay, ou null. */
    public function recordingEmbedUrl(): ?string
    {
        return StreamEmbed::toEmbedUrl($this->recording_url);
    }

    /**
     * Identifiants (account_id) des communicants dont la communication est
     * programmée dans cette session (via les créneaux) - les présentateurs.
     *
     * @return array<int, int>
     */
    public function presenterAccountIds(): array
    {
        $submissionIds = $this->slots()->where('submission_id', '>', 0)->pluck('submission_id');

        return Submission::whereIn('id', $submissionIds)
            ->with('participant')
            ->get()
            ->map(fn (Submission $s) => (int) $s->participant?->user_id)
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
    /**
     * Cette personne est-elle le modérateur ASSIGNÉ de la séance ?
     *
     * ⚠️ À ne pas confondre avec le rôle `Moderator` dans la salle, que le staff
     * obtient aussi : celui-ci dit que la séance lui a été confiée, par
     * rapprochement d'e-mail. C'est cette question-là, et non le rôle en salle,
     * qui décide de l'espace personnel (page « Séances à modérer », retour de
     * visio) - un administrateur n'a pas de séance à lui.
     */
    public function isModeratedBy(?User $account): bool
    {
        return $account !== null
            && filled($this->moderator_email)
            && mb_strtolower((string) $this->moderator_email) === mb_strtolower((string) $account->email);
    }

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

        $public = $this->meetingVisibilityEnum() === MeetingVisibility::Public;

        if (! $account) {
            return $public ? MeetingRole::Attendee : MeetingRole::None;
        }

        if ($this->isModeratedBy($account)) {
            return MeetingRole::Moderator;
        }

        if (in_array($account->id, $this->presenterAccountIds(), true)) {
            return MeetingRole::Presenter;
        }

        $isParticipant = Participant::where('event_id', $this->event_id)
            ->where('user_id', $account->id)
            ->exists();

        if ($public || $isParticipant) {
            return MeetingRole::Attendee;
        }

        return MeetingRole::None;
    }

    /**
     * Communications programmées dans la session (créneaux → soumissions), pour
     * la fiche « communicateurs » de la salle : ordre, auteur, titre, compte lié,
     * et la durée PRÉVUE du créneau.
     *
     * ⚠️ La durée vient du créneau et non d'un réglage de la salle : c'est le
     * programme publié qui fait foi, celui que l'auteur a reçu et sur lequel la
     * séance est annoncée. Elle vaut `null` quand le créneau n'a pas d'horaire de
     * fin - le chronomètre compte alors sans rien à dépasser.
     *
     * @return array<int, array{order: int, name: string, title: string, accountId: ?int, minutes: ?int}>
     */
    public function scheduledCommunications(): array
    {
        $slots = $this->slots()->where('submission_id', '>', 0)
            ->with('submission.participant')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $out = [];
        $order = 1;
        foreach ($slots as $slot) {
            $sub = $slot->submission;
            if (! $sub) {
                continue;
            }
            $name = $sub->correspondingAuthorName()
                ?: (collect($sub->authors ?? [])->pluck('name')->filter()->first() ?: 'Communicant');
            $minutes = $slot->starts_at && $slot->ends_at
                ? (int) round($slot->starts_at->diffInSeconds($slot->ends_at) / 60)
                : null;

            $out[] = [
                'order' => $order++,
                'name' => (string) $name,
                'title' => (string) $sub->title,
                'accountId' => $sub->participant?->user_id,
                'minutes' => $minutes && $minutes > 0 ? $minutes : null,
                // Le créneau et son passage : la salle valide SUR le créneau, et
                // un passage déjà constaté ne se redemande pas.
                'slotId' => $slot->id,
                'done' => $slot->wasPresented(),
            ];
        }

        return $out;
    }

    /** Sessions actuellement en direct. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('meeting_status', MeetingStatus::Live->value);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Atelier, $this> */
    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /** @return HasMany<Slot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(Slot::class, 'session_id');
    }

    /** @return HasOne<SessionReport, $this> */
    public function report(): HasOne
    {
        return $this->hasOne(SessionReport::class, 'session_id');
    }

    /** @return HasMany<SessionAttendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class, 'session_id');
    }

    // --- Présence (pointée par le modérateur / le rapporteur) --------------

    /**
     * Comptage par verdict, plus les non pointés.
     *
     * ⚠️ L'effectif attendu est celui des **auteurs des communications de la
     * séance** (`SessionRoster`), pas celui de l'événement : « 12 présents » sur
     * 215 inscrits ne voudrait rien dire pour une salle qui en attend 14.
     *
     * @return array{present: int, absent: int, unmarked: int, expected: int}
     */
    public function presenceCounts(): array
    {
        $parVerdict = $this->attendances()
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        $attendus = SessionRoster::participantIds($this)->count();
        $pointes = (int) $parVerdict->sum();

        return [
            'present' => (int) ($parVerdict['present'] ?? 0),
            'absent' => (int) ($parVerdict['absent'] ?? 0),
            // Le programme peut changer après un pointage : on ne descend jamais
            // sous zéro, et on ne prétend pas qu'il reste des gens à appeler.
            'unmarked' => max(0, $attendus - $pointes),
            'expected' => $attendus,
        ];
    }

    /** La présence a-t-elle été pointée (au moins une ligne) ? */
    public function hasPresenceMarked(): bool
    {
        return $this->attendances()->exists();
    }

    /**
     * Inscription de ce compte sur l'événement de la séance, portée dans l'identité
     * LiveKit : c'est elle qui permet de reconnaître un connecté dans la salle.
     */
    protected function meetingParticipantId(?User $account): ?int
    {
        if (! $account) {
            return null;
        }

        $id = Participant::where('event_id', $this->event_id)
            ->where(fn ($q) => $q->where('user_id', $account->id)
                ->orWhereRaw('LOWER(email) = ?', [mb_strtolower((string) $account->email)]))
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
}
