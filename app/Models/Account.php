<?php

namespace App\Models;

use App\Models\Concerns\HasNotificationPreferences;
use App\Models\Concerns\ParticipantIdentity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Lab404\Impersonate\Models\Impersonate;

/**
 * Compte participant : authentification propre à la plateforme (guard
 * « account »), distincte du back-office (guard « web » / {@see User}). Un
 * compte global par personne, valable pour toutes les événements. Peut être
 * impersonifié par le staff (assistance).
 *
 * @property string|null $two_factor_secret secret TOTP (chiffré)
 * @property array<int, string>|null $two_factor_recovery_codes codes de secours (chiffrés)
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $two_factor_method « totp » ou « sms »
 * @property string|null $two_factor_otp code OTP SMS haché
 * @property Carbon|null $two_factor_otp_expires_at
 * @property string|null $phone
 * @property string|null $locale langue préférée (i18n)
 * @property array<string, array{inapp: bool, email: bool}>|null $notification_preferences
 * @property array<string, mixed>|null $meta
 * @property string|null $orcid identifiant ORCID canonique (0000-0002-1825-0097)
 * @property Carbon|null $orcid_verified_at appartenance ORCID confirmée via OAuth
 */
class Account extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasNotificationPreferences, Impersonate, Notifiable, ParticipantIdentity;

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
}
