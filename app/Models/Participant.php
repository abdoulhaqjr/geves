<?php

namespace App\Models;

use App\Observers\ParticipantObserver;
use App\Support\EventNameFormat;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Participant : présence d'une personne sur un événement (auteur, auditeur,
 * évaluateur, membre de comité, co-auteur…). Reliée au compte global quand
 * connue.
 *
 * @property int|null $account_id
 * @property int|null $user_id
 * @property Carbon|null $checked_in_at
 * @property array<string, mixed>|null $meta
 * @property Event|null $event
 * @property User|null $user
 */
#[ObservedBy([ParticipantObserver::class])]
class Participant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /** Libellés des rôles. */
    public const ROLES = [
        'author' => 'Communicant',
        'coauthor' => 'Co-auteur',
        'reviewer' => 'Évaluateur',
        'committee' => 'Comité scientifique',
        'moderator' => 'Modérateur',
        'rapporteur' => 'Rapporteur',
        'attendee' => 'Auditeur',
    ];

    protected function casts(): array
    {
        return ['meta' => 'array', 'checked_in_at' => 'datetime'];
    }

    /** Le participant a-t-il été pointé (présent à l'accueil) ? */
    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * Présence réelle avérée : pointé à l'accueil, **ou** porté présent à au
     * moins une séance par son modérateur / rapporteur. Sert à conditionner
     * l'attestation de participation.
     *
     * ⚠️ Rien n'écrit `checked_in_at` depuis le retrait du scan de badge : la
     * présence en séance est devenue la seule source réelle. Sans elle, l'option
     * « présence requise » des attestations ne laissait passer personne.
     */
    public function wasPresent(): bool
    {
        return $this->isCheckedIn() || $this->attendedASession();
    }

    /** Porté présent à au moins une séance. */
    public function attendedASession(): bool
    {
        return $this->sessionAttendances()->where('status', 'present')->exists();
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    /** Modalité de présence commune à toutes les communications de cet événement. */
    public function participationModeLabel(): ?string
    {
        return match ($this->participation_mode) {
            'online' => 'En ligne',
            'onsite' => 'En présentiel',
            default => null,
        };
    }

    public function isInvited(): bool
    {
        return filled($this->meta['invited_at'] ?? null);
    }

    public function markInvited(): void
    {
        $meta = $this->meta ?? [];
        $meta['invited_at'] = now()->toDateTimeString();
        $this->update(['meta' => $meta]);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Utilisateur rattaché à cette inscription (identité unifiée - option B).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Submission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<SessionAttendance, $this> */
    public function sessionAttendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    /**
     * Paiements qui ouvrent les **droits de participation** (badge, attestations,
     * accès) : tout sauf les formations, qui sont réglées à part et rattachées au
     * même participant. C'est cette relation qu'il faut interroger pour savoir si
     * quelqu'un « a payé », jamais `payments()`.
     *
     * @return HasMany<Payment, $this>
     */
    public function participationPayments(): HasMany
    {
        return $this->hasMany(Payment::class)->forParticipation();
    }

    public function getFullNameAttribute(): string
    {
        return EventNameFormat::participant($this);
    }

    /**
     * L'identité affichée sur les documents de cet événement est-elle GELÉE ?
     *
     * Un instantané (`meta['identity']`) est pris à l'archivage de l'événement
     * (cf. FreezeEventIdentities). Tant qu'il existe, les documents dessinés en
     * direct (badges) lisent l'instantané plutôt que le compte - une correction
     * de profil (nom, photo, grade, affiliation…) ne retouche donc plus un
     * événement clôturé. Les attestations, elles, figent leur identité dès
     * l'émission (colonnes + `meta`), indépendamment de cet instantané.
     */
    public function isIdentityFrozen(): bool
    {
        $snap = ($this->meta ?? [])['identity'] ?? null;

        return is_array($snap) && $snap !== [];
    }

    /**
     * Identité effective pour les documents de cet événement : l'instantané gelé
     * s'il existe, sinon les valeurs « en direct ».
     *
     * @return array{name: string, civility: string, title: string, institution: string, phone: string, email: string, photo: string}
     */
    public function eventIdentity(): array
    {
        if ($this->isIdentityFrozen()) {
            $snap = (array) (($this->meta ?? [])['identity']);
            $photoPath = (string) ($snap['photo_path'] ?? '');

            return [
                'name' => EventNameFormat::parts(
                    (string) ($snap['first_name'] ?? ''),
                    (string) ($snap['last_name'] ?? ''),
                    EventNameFormat::orderForId($this->event_id),
                    (string) ($snap['name'] ?? ''),
                ),
                'civility' => (string) ($snap['civility'] ?? ''),
                'title' => (string) ($snap['title'] ?? ''),
                'institution' => (string) ($snap['institution'] ?? ''),
                'phone' => (string) ($snap['phone'] ?? ''),
                'email' => (string) ($snap['email'] ?? ''),
                'photo' => $photoPath !== '' ? Storage::url($photoPath) : '',
            ];
        }

        return $this->liveIdentity();
    }

    /**
     * Identité « en direct » : le compte lié d'abord (il porte le nom, la
     * civilité et la photo réels), repli sur les colonnes du participant. C'est
     * aussi ce qui est photographié dans l'instantané au gel.
     *
     * @return array{name: string, civility: string, title: string, institution: string, phone: string, email: string, photo: string, photo_path: string}
     */
    public function liveIdentity(): array
    {
        // ⚠️ On lit le compte lié SANS `?->` : Larastan type la relation BelongsTo
        // comme non-nulle (alors qu'elle est nullable au runtime), donc `$user?->x`
        // déclenche nullsafe.neverNull. On passe donc par une branche explicite.
        $user = $this->user;

        $firstName = (string) $this->first_name;
        $lastName = (string) $this->last_name;
        $name = $this->full_name;
        $civility = '';
        $grade = '';
        $affiliation = '';
        $userPhone = '';
        $userEmail = '';
        $photoPath = '';

        if ($user) {
            $firstName = (string) ($user->first_name ?: $this->first_name);
            $lastName = (string) ($user->last_name ?: $this->last_name);
            $name = EventNameFormat::parts($firstName, $lastName, EventNameFormat::orderForId($this->event_id),
                trim((string) $user->name) ?: (string) $user->email);
            $civility = (string) $user->civility;
            $grade = (string) $user->grade;
            $affiliation = (string) $user->affiliation;
            $userPhone = (string) $user->phone;
            $userEmail = (string) $user->email;
            $photoPath = (string) $user->photo_path;
        }

        return [
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'civility' => $civility,
            // Le grade est capté PAR ÉVÉNEMENT (participant.title) ; repli sur le profil.
            'title' => (string) ($this->title ?: $grade),
            'institution' => (string) ($this->institution ?: $affiliation),
            'phone' => (string) ($this->phone ?: $userPhone),
            'email' => (string) ($this->email ?: $userEmail),
            'photo' => $photoPath !== '' ? Storage::url($photoPath) : '',
            'photo_path' => $photoPath,
        ];
    }

    /** Nom affiché sur les documents de cet événement (gelé si l'événement l'est). */
    public function displayName(): string
    {
        return $this->eventIdentity()['name'];
    }

    /** URL de la photo à imprimer sur le badge (gelée si l'événement l'est), '' si aucune. */
    public function displayPhotoUrl(): string
    {
        return $this->eventIdentity()['photo'];
    }

    /** Affiliation affichée sur les documents de cet événement. */
    public function displayInstitution(): string
    {
        return $this->eventIdentity()['institution'];
    }

    /** Au moins une communication acceptée (canonique) sur l'événement. */
    public function hasAcceptedSubmission(): bool
    {
        return $this->submissions()
            ->where('revision_of', 0)
            ->where('status', 'accepted')
            ->exists();
    }

    /** Le participant a déjà un paiement réglé (garde anti-double-paiement). */
    public function hasPaid(): bool
    {
        return $this->participationPayments()->where('status', 'paid')->exists();
    }
}
