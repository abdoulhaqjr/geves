<?php

namespace App\Models;

use App\Models\Concerns\HasNotificationPreferences;
use App\Notifications\StaffVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Traits\HasRoles;

/**
 * Utilisateur du back-office (staff de gestion des colloques). Les rôles et
 * permissions sont gérés par spatie/laravel-permission ; la capacité clé est
 * `manage_colloques`. Peut impersonifier un compte participant (guard account).
 * Vérifiable par e-mail (le renvoi du lien est déclenché depuis la page
 * Utilisateurs) — non bloquant à la connexion.
 *
 * @property array<string, array{inapp: bool, email: bool}>|null $notification_preferences
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property bool $is_active
 * @property string|null $notes
 */
#[Fillable(['name', 'email', 'password', 'is_active', 'notes'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasNotificationPreferences, HasRoles, Impersonate, Notifiable;

    /** Seul le staff habilité peut impersonifier. */
    public function canImpersonate(): bool
    {
        return $this->can('manage_colloques');
    }

    /** Aucun Mailable dédié côté staff : l'e-mail passe par le canal générique. */
    protected function usesDedicatedMailFor(string $kind): bool
    {
        return false;
    }

    /** Lien de vérification dédié au guard « web » (route admin signée). */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new StaffVerifyEmail);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }
}
