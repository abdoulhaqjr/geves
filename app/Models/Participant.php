<?php

namespace App\Models;

use App\Observers\ParticipantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Participant : présence d'une personne sur une édition (auteur, auditeur,
 * relecteur, membre de comité, co-auteur…). Reliée au compte global quand
 * connue.
 *
 * @property \Illuminate\Support\Carbon|null $checked_in_at
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
        'committee' => 'Comité',
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

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

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
