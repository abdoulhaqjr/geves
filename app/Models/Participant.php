<?php

namespace App\Models;

use App\Observers\ParticipantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Participant : présence d'une personne sur une événement (auteur, auditeur,
 * relecteur, membre de comité, co-auteur…). Reliée au compte global quand
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
        'reviewer' => 'Relecteur',
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
        return trim($this->first_name.' '.$this->last_name);
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
