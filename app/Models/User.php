<?php

namespace App\Models;

use App\Models\Concerns\HasNotificationPreferences;
use App\Models\Concerns\ParticipantIdentity;
use App\Notifications\AccountResetPassword;
use App\Notifications\AccountVerifyEmail;
use App\Notifications\StaffVerifyEmail;
use App\Support\Locales;
use App\Support\Phone;
use App\Support\StaffRoles;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\Permission\Traits\HasRoles;

/**
 * Utilisateur du back-office (staff de gestion des colloques). Les rôles et
 * permissions sont gérés par spatie/laravel-permission ; la capacité clé est
 * `manage_colloques`. Peut impersonifier un compte participant (guard account).
 * Vérifiable par e-mail (le renvoi du lien est déclenché depuis la page
 * Utilisateurs) - non bloquant à la connexion.
 *
 * ⚠️ **`access_level` est annoncé NULLABLE**, et ce n'est pas de la prudence :
 * son défaut `'user'` vit en BASE, pas dans le modèle, donc une instance tout
 * juste créée le porte à `null` jusqu'au rechargement. Annoncé `string`, il
 * faisait passer tout contrôle de nullité pour « toujours vrai » à l'analyse -
 * si bien qu'on était invité à retirer précisément la garde qui tient ce cas.
 *
 * @property array<string, array{inapp: bool, email: bool}>|null $notification_preferences
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property bool $is_active
 * @property string|null $access_level
 * @property string|null $notes
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $affiliation
 * @property string|null $orcid
 * @property Carbon|null $orcid_verified_at
 * @property string|null $two_factor_secret
 * @property array<int, string>|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $two_factor_method
 * @property string|null $two_factor_otp
 * @property Carbon|null $two_factor_otp_expires_at
 * @property bool $must_reset_password
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'email', 'password', 'is_active', 'notes', 'access_level',
    'civility', 'first_name', 'last_name', 'grade', 'phone', 'affiliation',
    'orcid', 'country', 'city', 'bio', 'photo_path', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasNotificationPreferences, HasRoles, Impersonate, Notifiable, ParticipantIdentity, SoftDeletes;

    /**
     * Rôles & permissions spatie toujours résolus sous le guard « web » : depuis
     * l'identité unifiée, un User est authentifiable via « web » ET « account »,
     * mais ses rôles back-office vivent sous « web ». Évite l'ambiguïté (spatie
     * cherchait sinon une permission sous le guard « account »).
     */
    protected string $guard_name = 'web';

    /** Maintient le nom affiché en phase avec l'identité du profil. */
    protected static function booted(): void
    {
        static::saving(function (self $user) {
            $profileName = trim(((string) $user->first_name).' '.((string) $user->last_name));

            if ($profileName !== '' && $user->isDirty(['first_name', 'last_name'])) {
                $user->name = mb_substr($profileName, 0, 255);
            } elseif (blank($user->name)) {
                $user->name = $profileName !== '' ? mb_substr($profileName, 0, 255) : (string) $user->email;
            }
        });
    }

    /**
     * Qui peut impersonifier un compte : UNIQUEMENT l'administrateur de colloque
     * et le super-administrateur ({@see isColloqueAdmin}). Un gestionnaire ou un
     * membre du secrétariat, même avec la capacité « participants », ne le peut pas -
     * « se connecter en tant que » donne accès à tout l'espace de la personne.
     */
    public function canImpersonate(): bool
    {
        return $this->isColloqueAdmin();
    }

    /**
     * Affectations du staff à des événements (fonction : organisation, secrétariat,
     * comptabilité…). Un utilisateur peut cumuler plusieurs fonctions par événement.
     *
     * @return BelongsToMany<Event, $this>
     */
    public function assignedEditions(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user')->withPivot('role')->withTimestamps();
    }

    /** Niveaux d'accès globaux (refonte Phase 3). */
    public const ACCESS_SUPER_ADMIN = 'super_admin';

    public const ACCESS_COLLOQUE_ADMIN = 'colloque_admin';

    public const ACCESS_USER = 'user';

    /** @return array<string, string> niveau => libellé */
    public static function accessLevels(): array
    {
        return [
            self::ACCESS_SUPER_ADMIN => 'Super administrateur',
            // ⚠️ La CLÉ reste `colloque_admin` (identifiant, enregistré tel quel
            // en base) ; seul le LIBELLÉ suit le vocabulaire du projet. GEVES ne
            // gère plus que des colloques : séminaires et journées scientifiques
            // relèvent du même niveau d'accès.
            self::ACCESS_COLLOQUE_ADMIN => 'Administrateur d’événements',
            self::ACCESS_USER => 'Utilisateur',
        ];
    }

    /**
     * Ce que chaque niveau ouvre VRAIMENT, pour l'écran qui le choisit.
     *
     * ⚠️ Le niveau d'accès est le réglage le plus lourd de la fiche d'un compte :
     * {@see isColloqueSuperAdmin()} en découle, et le `Gate::before` ouvre alors
     * TOUT. Il était pourtant proposé comme une liste déroulante parmi les champs
     * d'identité, sous une légende d'une ligne qui confondait les trois choix.
     *
     * @return array<string, string> niveau => ce qu'il ouvre
     */
    public static function accessLevelHints(): array
    {
        return [
            self::ACCESS_SUPER_ADMIN => 'Toute la plateforme : tous les événements, les comptes et les réglages. À réserver.',
            self::ACCESS_COLLOQUE_ADMIN => 'Crée ses propres événements et y nomme le gestionnaire. N’atteint ni les comptes ni les réglages de la plateforme.',
            self::ACCESS_USER => 'Aucun accès global : n’ouvre que les événements où le compte est affecté, et seulement ce que sa fonction y permet. C’est le niveau de l’immense majorité.',
        ];
    }

    /**
     * Super-administrateur back-office (toute la plateforme). Basé sur le niveau
     * d'accès ; on garde le repli spatie (`administrator` / `manage_colloques`)
     * le temps de la bascule complète.
     */
    public function isColloqueSuperAdmin(): bool
    {
        return $this->access_level === self::ACCESS_SUPER_ADMIN
            || $this->hasRole('administrator')
            || $this->getAllPermissions()->contains('name', 'manage_colloques');
    }

    /** Administrateur de colloque (crée + gère/co-gère des colloques) - ou super-admin. */
    public function isColloqueAdmin(): bool
    {
        return $this->isColloqueSuperAdmin() || $this->access_level === self::ACCESS_COLLOQUE_ADMIN;
    }

    /** Peut créer un nouveau colloque / une nouvelle événement (super-admin ou admin de colloque). */
    public function canCreateColloque(): bool
    {
        return $this->isColloqueAdmin();
    }

    /**
     * Fonctions du staff sur un événement donné.
     *
     * @return list<string>
     */
    public function rolesOnEdition(Event|int $event): array
    {
        $id = $event instanceof Event ? (int) $event->id : (int) $event;

        return $this->assignedEditions()->where('events.id', $id)->get()
            ->pluck('pivot.role')->unique()->values()->all();
    }

    /** L'utilisateur peut-il accéder à cet événement (admin global, ou affecté) ? */
    public function canAccessEdition(Event|int $event): bool
    {
        return $this->isColloqueSuperAdmin() || $this->rolesOnEdition($event) !== [];
    }

    /**
     * Capacités effectives de l'utilisateur SUR un événement (union des matrices
     * de ses fonctions ; toutes les capacités pour un super-admin).
     *
     * @return list<string>
     */
    public function capabilitiesOnEdition(Event|int $event): array
    {
        if ($this->isColloqueSuperAdmin()) {
            return array_keys(StaffRoles::PERMISSIONS);
        }

        $caps = [];
        foreach ($this->rolesOnEdition($event) as $role) {
            $caps = array_merge($caps, StaffRoles::permissionsFor($role));
        }

        return array_values(array_unique($caps));
    }

    /** L'utilisateur a-t-il la capacité `$capability` sur cet événement ? */
    public function canOnEdition(string $capability, Event|int $event): bool
    {
        return $this->isColloqueSuperAdmin()
            || in_array($capability, $this->capabilitiesOnEdition($event), true);
    }

    /**
     * Identifiants des événements accessibles à l'utilisateur (toutes s'il est
     * super-admin ; sinon celles où il est affecté - filtrées par capacité si
     * fournie).
     *
     * @return list<int>
     */
    public function accessibleEditionIds(?string $capability = null): array
    {
        if ($this->isColloqueSuperAdmin()) {
            return Event::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $ids = [];
        foreach (DB::table('event_user')->where('user_id', $this->id)->get(['event_id', 'role']) as $row) {
            if ($capability === null || in_array($capability, StaffRoles::permissionsFor((string) $row->role), true)) {
                $ids[(int) $row->event_id] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /**
     * Événements accessibles à l'utilisateur (pour les listes déroulantes du
     * back-office), filtrées par capacité si fournie.
     *
     * @return Collection<int, Event>
     */
    public function accessibleEditions(?string $capability = null): Collection
    {
        return Event::whereIn('id', $this->accessibleEditionIds($capability))->latest('id')->get();
    }

    /**
     * L'utilisateur a-t-il la capacité sur AU MOINS un événement (pilote la
     * visibilité des sections du menu) ? Sans capacité : au moins une affectation.
     */
    public function canAnyEdition(?string $capability = null): bool
    {
        return $this->isColloqueSuperAdmin() || $this->accessibleEditionIds($capability) !== [];
    }

    // --- Fonctions événementielles (côté participant, par e-mail / inscription) ---

    /**
     * Séances où cet utilisateur est le modérateur assigné (rapproché par e-mail).
     *
     * @return Builder<EventSession>
     */
    public function sessionsAsModerator(): Builder
    {
        return EventSession::query()
            ->whereRaw('LOWER(moderator_email) = ?', [mb_strtolower((string) $this->email)]);
    }

    /**
     * Séances où cet utilisateur est le rapporteur assigné (rapproché par e-mail).
     *
     * @return Builder<EventSession>
     */
    public function sessionsAsRapporteur(): Builder
    {
        return EventSession::query()
            ->whereRaw('LOWER(rapporteur_email) = ?', [mb_strtolower((string) $this->email)]);
    }

    /**
     * Inscriptions « évaluateur » de cet utilisateur (par événement, jeton de portail).
     *
     * @return Builder<Participant>
     */
    public function reviewerParticipations(): Builder
    {
        return Participant::query()->where('user_id', $this->id)->where('role', 'reviewer');
    }

    /** A-t-il une séance à animer ? (pilote la visibilité du menu) */
    public function isModeratorSomewhere(): bool
    {
        return filled($this->email) && $this->sessionsAsModerator()->exists();
    }

    /** A-t-il une séance à rapporter ? */
    public function isRapporteurSomewhere(): bool
    {
        return filled($this->email) && $this->sessionsAsRapporteur()->exists();
    }

    /** A-t-il des évaluations qui lui sont confiées ? */
    public function isReviewerSomewhere(): bool
    {
        return $this->reviewerParticipations()->exists();
    }

    /**
     * Langue de rendu des notifications (e-mails) : celle choisie par la personne
     * (`locale`), repli français. Laravel enveloppe l'envoi dans cette locale, donc
     * les e-mails de compte sont rédigés dans la langue de l'utilisateur.
     */
    public function preferredLocale(): string
    {
        return Locales::resolve($this->locale);
    }

    /**
     * Nom à utiliser pour saluer la personne dans un e-mail : civilité + prénom
     * nom quand ils sont connus (« Dr Awa Doumbia »), sinon null pour retomber
     * sur un accueil générique (« Bonjour, »). ⚠️ On n'utilise jamais `name`,
     * qui peut valoir l'e-mail faute de prénom/nom : saluer par une adresse
     * serait pire que l'accueil générique.
     */
    public function salutationName(): ?string
    {
        $name = trim(((string) $this->first_name).' '.((string) $this->last_name));

        if ($name === '') {
            return null;
        }

        $civility = trim((string) $this->civility);

        return $civility !== '' ? $civility.' '.$name : $name;
    }

    /**
     * Vérification d'e-mail par défaut = flux participant (route account signée,
     * via VerifyEmail::createUrlUsing). Les comptes staff créés au back-office
     * reçoivent explicitement {@see StaffVerifyEmail}.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new AccountVerifyEmail);
    }

    /** Lien de vérification staff (route admin signée) - création back-office. */
    public function sendStaffEmailVerificationNotification(): void
    {
        $this->notify(new StaffVerifyEmail);
    }

    /**
     * Réinitialisation de mot de passe : e-mail rédigé et localisé {@see
     * AccountResetPassword}, en lieu et place de la notification anglaise par
     * défaut de Laravel. Vaut pour les participants ET le staff (comptes unifiés).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AccountResetPassword($token));
    }

    /**
     * Téléphone : forme canonique **à l'écriture**.
     *
     * ⚠️ C'est un identifiant fort (rapprochement auteurs/formateurs, 2FA SMS) et
     * il est désormais unique. Sans normalisation au stockage, « +228 90 01 52 84 »
     * et « +22890015284 » seraient deux valeurs distinctes : l'unicité serait
     * contournable par la simple mise en forme et le rapprochement échouerait.
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => Phone::normalize($value) ?: null);
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
            // Identité unifiée (Phase 1 - profil repris d'`accounts`).
            'meta' => 'array',
            'orcid_verified_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_otp_expires_at' => 'datetime',
            'must_reset_password' => 'boolean',
        ];
    }

    /**
     * Champs de profil manquants pour pouvoir payer (identité + téléphone +
     * affiliation, e-mail vérifié). Le **téléphone** est requis parce qu'il
     * pré-remplit la page de la passerelle (mobile money). Retourne les libellés
     * des champs à compléter, dans l'ordre du formulaire de profil.
     *
     * @return list<string>
     */
    public function missingProfileFields(): array
    {
        $missing = [];
        foreach ([
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'phone' => 'Téléphone',
            'affiliation' => 'Affiliation',
        ] as $field => $label) {
            if (blank($this->{$field})) {
                $missing[] = $label;
            }
        }
        if (! $this->hasVerifiedEmail()) {
            $missing[] = 'E-mail vérifié';
        }

        return $missing;
    }

    /** Profil suffisamment complet pour engager un paiement. */
    public function profileComplete(): bool
    {
        return $this->missingProfileFields() === [];
    }
}
