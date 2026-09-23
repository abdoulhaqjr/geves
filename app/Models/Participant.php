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
 * Participant : présence d'une personne sur une édition (auteur, auditeur,
 * relecteur, membre de comité, co-auteur…). Reliée au compte global quand
 * connue.
 *
 * @property int|null $account_id
 * @property int|null $user_id
 * @property Carbon|null $checked_in_at
 * @property array<string, mixed>|null $meta
 * @property Edition|null $edition
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
     * Présence réelle avérée (pointé à l'accueil). Sert à conditionner
     * l'attestation de participation.
     */
    public function wasPresent(): bool
    {
        return $this->isCheckedIn();
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

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
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

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /** Au moins une communication acceptée (canonique) sur l'édition. */
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
        return $this->payments()->where('status', 'paid')->exists();
    }
}
