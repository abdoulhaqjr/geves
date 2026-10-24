<?php

namespace App\Models;

use App\Documents\AttestationVars;
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
 * Participant : présence d'une personne sur un événement (auteur, participant simple,
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

    /** Rôles affichés dans le registre des participants de l'événement. */
    public const PARTICIPATION_ROLES = ['author', 'coauthor', 'attendee'];

    protected $guarded = ['id'];

    /**
     * Libellés des rôles.
     *
     * ⚠️⚠️ **UN RÔLE SANS LIBELLÉ SE RETOURNE EN ANGLAIS, SANS RIEN CASSER.**
     * {@see roleLabel()} retombe sur `ucfirst($role)` : `trainee` et `trainer`,
     * posés par l'inscription à une formation et par l'ajout d'un formateur,
     * s'affichaient donc **« Trainee »** et **« Trainer »** - dans la fiche d'un
     * participant, dans l'export CSV du registre, dans la répartition par rôle
     * des rapports, et jusque dans le jeton `{role}` d'une **attestation**
     * ({@see AttestationVars}). Aucune erreur, aucun test rouge :
     * juste de l'anglais au milieu d'un document officiel en français.
     *
     * ⚠️ **Tout rôle que le code attribue doit avoir sa ligne ici.** Un cas de
     * `RolesDeParticipantTest` balaie `app/` et échoue si un `role:` nouveau
     * apparaît sans libellé.
     */
    public const ROLES = [
        'author' => 'Communicant',
        'coauthor' => 'Co-auteur',
        'reviewer' => 'Évaluateur',
        'former_reviewer' => 'Ancien évaluateur',
        'committee' => 'Comité scientifique',
        'moderator' => 'Modérateur',
        'rapporteur' => 'Rapporteur',
        'attendee' => 'Participant simple',
        // ⚠️ Ces deux-là viennent du module Formation, et ne disent RIEN de la
        // participation à l'événement : on peut suivre un atelier sans s'être
        // inscrit au colloque, et un paiement de formation n'ouvre aucun droit
        // ({@see participationPayments()}).
        'trainee' => 'Inscrit à une formation',
        'trainer' => 'Formateur',
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

    /**
     * ⚠️⚠️ **QUI DOIT DES FRAIS ? LA QUESTION N'EST PAS « A-T-IL UNE
     * COMMUNICATION ACCEPTÉE ».** Le paiement était gardé par
     * {@see hasAcceptedSubmission()} seul : un **participant simple** - celui
     * qui vient écouter - ne pouvait donc rien régler, et la page lui disait de
     * « contacter l'organisation ». Le rôle « Participant simple » existait
     * pourtant déjà dans {@see ROLES}, et la page publique promettait de
     * pouvoir s'inscrire ainsi.
     *
     * Deux populations, un seul invariant : **on doit des frais quand une
     * communication a été retenue, ou quand on s'est inscrit ET que
     * l'organisation a ouvert le paiement aux participants simples.**
     *
     * ⚠️ **Les deux moments ne se confondent pas** : on s'inscrit dès que les
     * inscriptions sont ouvertes, on règle quand l'organisation le dit - en
     * général après la clôture des dépôts
     * ({@see Event::attendeePaymentIsOpen()}). Sans cette seconde porte, une
     * inscription ouvrait aussitôt un encaissement que personne n'avait décidé.
     *
     * ⚠️ Le communicant, lui, ne dépend PAS de ce réglage : sa décision est
     * tombée, ses frais sont dus.
     */
    public function owesParticipationFees(): bool
    {
        if ($this->hasAcceptedSubmission()) {
            return true;
        }

        return $this->isRegisteredAttendee() && (bool) $this->event?->attendeePaymentIsOpen();
    }

    /**
     * S'est-il inscrit lui-même à l'événement ?
     *
     * ⚠️ **C'est une DATE, pas le rôle.** Un participant simple qui dépose
     * ensuite un résumé passe en `author` ({@see UpsertParticipantForAccount}) :
     * lu sur le rôle, il perdrait d'un coup le droit de régler l'inscription
     * qu'il venait de prendre, et jusqu'à l'acceptation de son résumé. Une
     * inscription est un fait daté ; elle ne se défait pas parce qu'on a changé
     * de casquette.
     */
    public function isRegisteredAttendee(): bool
    {
        return filled($this->meta['registered_at'] ?? null);
    }

    /** Le participant a déjà un paiement réglé (garde anti-double-paiement). */
    public function hasPaid(): bool
    {
        return $this->participationPayments()->where('status', 'paid')->exists();
    }
}
