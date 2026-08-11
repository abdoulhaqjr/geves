<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Lab404\Impersonate\Models\Impersonate;

/**
 * Compte participant : authentification propre à la plateforme (guard
 * « account »), distincte du back-office (guard « web » / {@see User}). Un
 * compte global par personne, valable pour toutes les éditions. Peut être
 * impersonifié par le staff (assistance).
 *
 * @property string|null $two_factor_secret secret TOTP (chiffré)
 * @property array<int, string>|null $two_factor_recovery_codes codes de secours (chiffrés)
 * @property Carbon|null $two_factor_confirmed_at
 */
class Account extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Impersonate, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'meta' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** La double authentification (TOTP) est active et confirmée. */
    public function hasTotpEnabled(): bool
    {
        return filled($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /**
     * (Re)génère les codes de récupération à usage unique et les affecte au
     * modèle (retourne les codes en clair pour affichage unique). Ne persiste
     * pas : appeler save() ensuite.
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = strtoupper(bin2hex(random_bytes(5))); // 10 caractères hex
            $codes[] = substr($raw, 0, 5).'-'.substr($raw, 5, 5);
        }

        $this->two_factor_recovery_codes = $codes;

        return $codes;
    }

    /** Nombre de codes de récupération restants. */
    public function countRecoveryCodes(): int
    {
        return count((array) ($this->two_factor_recovery_codes ?? []));
    }

    /**
     * Consomme (retire) un code de récupération s'il correspond. Insensible à la
     * casse et aux espaces. Ne persiste pas : appeler save() ensuite.
     */
    public function consumeRecoveryCode(string $code): bool
    {
        $code = strtoupper(trim($code));
        $codes = (array) ($this->two_factor_recovery_codes ?? []);

        $idx = array_search($code, array_map('strtoupper', $codes), true);
        if ($idx === false) {
            return false;
        }

        unset($codes[$idx]);
        $this->two_factor_recovery_codes = array_values($codes);

        return true;
    }

    /** Désactive complètement la 2FA et purge les secrets. Ne persiste pas. */
    public function disableTotp(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
    }

    /** Présences de ce compte sur les différentes éditions. */
    public function participations(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        $a = mb_substr($this->first_name, 0, 1);
        $b = mb_substr($this->last_name, 0, 1);

        return mb_strtoupper($a.$b) ?: 'U';
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
