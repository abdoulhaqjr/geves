<?php

namespace App\Models;

use App\Models\Concerns\HasNotificationPreferences;
use App\Support\Orcid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
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
 * @property string|null $two_factor_method « totp » ou « sms »
 * @property string|null $two_factor_otp code OTP SMS haché
 * @property Carbon|null $two_factor_otp_expires_at
 * @property string|null $phone
 * @property array<string, array{inapp: bool, email: bool}>|null $notification_preferences
 * @property array<string, mixed>|null $meta
 * @property string|null $orcid identifiant ORCID canonique (0000-0002-1825-0097)
 * @property Carbon|null $orcid_verified_at appartenance ORCID confirmée via OAuth
 */
class Account extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasNotificationPreferences, Impersonate, Notifiable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'meta' => 'array',
            'notification_preferences' => 'array',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_otp_expires_at' => 'datetime',
            'orcid_verified_at' => 'datetime',
        ];
    }

    /** Lien vérifiable vers la fiche ORCID, ou null si non renseigné/invalide. */
    public function orcidUrl(): ?string
    {
        return filled($this->orcid) ? Orcid::url((string) $this->orcid) : null;
    }

    /** L'ORCID a-t-il été confirmé par l'utilisateur via OAuth ? */
    public function orcidVerified(): bool
    {
        return $this->orcid_verified_at !== null && filled($this->orcid);
    }

    /** La double authentification par application (TOTP) est active et confirmée. */
    public function hasTotpEnabled(): bool
    {
        return filled($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /** La double authentification par SMS (OTP) est active et confirmée. */
    public function hasSmsEnabled(): bool
    {
        return $this->two_factor_method === 'sms' && ! is_null($this->two_factor_confirmed_at);
    }

    /** Une méthode de 2FA (TOTP ou SMS) est active. */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->hasTotpEnabled() || $this->hasSmsEnabled();
    }

    /** Méthode de 2FA active (« totp », « sms » ou null). */
    public function twoFactorMethod(): ?string
    {
        return match (true) {
            $this->hasSmsEnabled() => 'sms',
            $this->hasTotpEnabled() => 'totp',
            default => null,
        };
    }

    /**
     * Génère un code OTP à 6 chiffres (haché + expiration) pour un envoi SMS et
     * retourne le code en clair (à transmettre). Ne persiste pas : appeler save().
     */
    public function generateOtp(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->two_factor_otp = Hash::make($code);
        $this->two_factor_otp_expires_at = now()->addMinutes((int) config('sms.otp_ttl', 5));

        return $code;
    }

    /** Vérifie un code OTP (non expiré) et le consomme. Ne persiste pas. */
    public function verifyOtp(string $code): bool
    {
        if (! filled($this->two_factor_otp) || ! $this->two_factor_otp_expires_at
            || $this->two_factor_otp_expires_at->isPast()) {
            return false;
        }

        if (! Hash::check(trim($code), (string) $this->two_factor_otp)) {
            return false;
        }

        $this->two_factor_otp = null;
        $this->two_factor_otp_expires_at = null;

        return true;
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

    /** Désactive complètement la 2FA (TOTP et SMS) et purge les secrets. Ne persiste pas. */
    public function disableTotp(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->two_factor_method = null;
        $this->two_factor_otp = null;
        $this->two_factor_otp_expires_at = null;
    }

    /**
     * Présences de ce compte sur les différentes éditions.
     *
     * @return HasMany<Participant, $this>
     */
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
