<?php

namespace App\Models;

use App\Models\Concerns\HasNotificationPreferences;
use App\Notifications\StaffVerifyEmail;
use App\Support\StaffRoles;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Affectations du staff à des éditions (fonction : organisation, secrétariat,
     * comptabilité…). Un utilisateur peut cumuler plusieurs fonctions par édition.
     *
     * @return BelongsToMany<Edition, $this>
     */
    public function assignedEditions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_user')->withPivot('role')->withTimestamps();
    }

    /** Super-administrateur back-office (capacité globale, transverse aux colloques). */
    public function isColloqueSuperAdmin(): bool
    {
        return $this->hasRole('administrator') || $this->getAllPermissions()->contains('name', 'manage_colloques');
    }

    /**
     * Fonctions du staff sur une édition donnée.
     *
     * @return list<string>
     */
    public function rolesOnEdition(Edition|int $edition): array
    {
        $id = $edition instanceof Edition ? (int) $edition->id : (int) $edition;

        return $this->assignedEditions()->where('editions.id', $id)->get()
            ->pluck('pivot.role')->unique()->values()->all();
    }

    /** L'utilisateur peut-il accéder à cette édition (admin global, ou affecté) ? */
    public function canAccessEdition(Edition|int $edition): bool
    {
        return $this->isColloqueSuperAdmin() || $this->rolesOnEdition($edition) !== [];
    }

    /**
     * Capacités effectives de l'utilisateur SUR une édition (union des matrices
     * de ses fonctions ; toutes les capacités pour un super-admin).
     *
     * @return list<string>
     */
    public function capabilitiesOnEdition(Edition|int $edition): array
    {
        if ($this->isColloqueSuperAdmin()) {
            return array_keys(StaffRoles::PERMISSIONS);
        }

        $caps = [];
        foreach ($this->rolesOnEdition($edition) as $role) {
            $caps = array_merge($caps, StaffRoles::permissionsFor($role));
        }

        return array_values(array_unique($caps));
    }

    /** L'utilisateur a-t-il la capacité `$capability` sur cette édition ? */
    public function canOnEdition(string $capability, Edition|int $edition): bool
    {
        return $this->isColloqueSuperAdmin()
            || in_array($capability, $this->capabilitiesOnEdition($edition), true);
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
