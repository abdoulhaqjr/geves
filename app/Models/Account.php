<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Compte participant : authentification propre à la plateforme (guard
 * « account »), distincte du back-office (guard « web » / {@see User}). Un
 * compte global par personne, valable pour toutes les éditions.
 */
class Account extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'meta' => 'array',
        ];
    }

    /** Présences de ce compte sur les différentes éditions. */
    public function participations(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        $a = mb_substr($this->first_name, 0, 1);
        $b = mb_substr($this->last_name, 0, 1);

        return mb_strtoupper($a . $b) ?: 'U';
    }

    /**
     * Règle de base pour soumettre : e-mail vérifié + profil complet
     * (affiliation renseignée). La photo reste optionnelle.
     */
    public function canSubmit(): bool
    {
        return $this->hasVerifiedEmail() && filled($this->affiliation);
    }
}
