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
            // en base) ; seul le LIBELLÉ suit le vocabulaire du projet. Et il ne
            // le restreint PAS aux événements : cet administrateur a la main sur
            // toute la plateforme - comptes, rôles et réglages compris - sauf ce
            // qui est strictement réservé au super administrateur.
            self::ACCESS_COLLOQUE_ADMIN => 'Administrateur',
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
            self::ACCESS_SUPER_ADMIN => 'Tout ce qu’ouvre un administrateur, plus la seule chose qui lui est refusée : nommer un super administrateur, et voir ces comptes. À réserver.',
            self::ACCESS_COLLOQUE_ADMIN => 'Crée les événements et y attache leur gestionnaire. Ouvre TOUTE la plateforme : tous les événements, les comptes, les rôles et les réglages. Ne peut pas nommer de super administrateur.',
            self::ACCESS_USER => 'Aucun accès global : n’ouvre que les événements où le compte est affecté, et seulement ce que sa fonction y permet. C’est le niveau de l’immense majorité.',
        ];
    }

    /**
     * ⚠️⚠️ **LA SEULE CHOSE QU'UN ADMINISTRATEUR NE PEUT PAS FAIRE.** Il a la
     * main sur tout le reste - les événements, les comptes, les rôles, les
     * réglages - mais il ne **nomme pas** de super administrateur : sans cette
     * réserve, il se promeut lui-même en deux clics et le niveau au-dessus du
     * sien ne veut plus rien dire. C'est, avec {@see voitLeCompte()}, tout ce
     * qui distingue encore les deux niveaux.
     *
     * ⚠️ **Et ce n'est pas qu'une liste d'affichage** : la fiche d'un compte
     * valide sur elle (`UserController::validateUser()`), parce que retirer un
     * choix du formulaire ne ferme aucune porte - un `access_level` posté à la
     * main en ouvrirait une grande.
     *
     * ⚠️ Révoquer un super administrateur est déjà hors de portée par un autre
     * chemin : l'administrateur ne **voit** pas ces comptes, donc les cinq
     * actions qui prennent un `{user}` lui répondent 404.
     *
     * @return array<string, string> niveau => libellé
     */
    public static function niveauxAttribuablesPar(?self $viewer): array
    {
        $niveaux = self::accessLevels();

        if (! $viewer?->isColloqueSuperAdmin()) {
            unset($niveaux[self::ACCESS_SUPER_ADMIN]);
        }

        return $niveaux;
    }

    /**
     * ⚠️⚠️ **UN SUPER ADMINISTRATEUR N'EST VISIBLE QUE D'UN SUPER ADMINISTRATEUR.**
     * L'administrateur a la main sur les comptes - c'est lui qui distribue les
     * rôles - mais pas sur le seul niveau placé au-dessus du sien : le voir dans
     * la liste, c'est pouvoir ouvrir sa fiche, lui envoyer une réinitialisation
     * de mot de passe, le désactiver ou le supprimer.
     *
     * ⚠️ **Une seule définition**, consommée par la page Utilisateurs ET par
     * l'annuaire du copilote (`App\Support\UserDirectory`) : masquer dans la
     * liste sans fermer la fiche ni l'assistant ne masquerait rien du tout.
     * ⚠️ Nommé en toute lettre, SANS `{@see}` : Pint en ferait un `use`, et un
     * import se lit comme une dépendance - c'est ce que garde `AnnuaireComptesTest`.
     *
     * ⚠️ `!=` ÉCARTE LES NULL en SQL, et `access_level` est nullable (son défaut
     * vit en base) : sans `orWhereNull`, tout compte au niveau non renseigné
     * disparaîtrait de la liste.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeVisiblesPar(Builder $query, ?self $viewer): Builder
    {
        if ($viewer?->isColloqueSuperAdmin()) {
            return $query;
        }

        return $query->whereNotIn('users.id', self::query()->superAdministrateurs()->select('users.id'));
    }

    /** Ce compte a-t-il le droit de voir `$cible` ? {@see scopeVisiblesPar()}. */
    public static function voitLeCompte(?self $viewer, self $cible): bool
    {
        return (bool) $viewer?->isColloqueSuperAdmin()
            || ! $cible->isColloqueSuperAdmin();
    }

    /** Même définition SQL que isColloqueSuperAdmin(), pour les listes et compteurs. */
    public function scopeSuperAdministrateurs(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('access_level', self::ACCESS_SUPER_ADMIN)
            ->orWhereHas('roles', fn (Builder $r) => $r
                ->where('name', 'administrator')
                ->orWhereHas('permissions', fn (Builder $p) => $p->where('name', 'manage_colloques')))
            ->orWhereHas('permissions', fn (Builder $p) => $p->where('name', 'manage_colloques')));
    }

    /**
     * ⚠️⚠️ **LA RÉSERVE DU SUPER ADMINISTRATEUR - et elle ne sert plus qu'à ça.**
     *
     * Ce prédicat a longtemps voulu dire « ouvre toute la plateforme » : c'est
     * {@see isColloqueAdmin()} qui le dit désormais, et l'administrateur a
     * **accès à tout** - tous les événements, les comptes, les rôles et les
     * réglages. Il ne reste au super administrateur que **deux** choses, et
     * elles tiennent ensemble : **nommer ou révoquer un super administrateur**
     * ({@see niveauxAttribuablesPar()}) et **voir ces comptes**
     * ({@see scopeVisiblesPar()}, {@see voitLeCompte()}). Sans cette réserve,
     * un administrateur se promeut lui-même et le niveau au-dessus du sien
     * devient décoratif.
     *
     * ⚠️ **Écrire `isColloqueSuperAdmin()` dans une garde d'accès est donc
     * presque toujours une erreur** : c'était le réflexe de tout le code, et il
     * referme à l'administrateur ce qu'on vient de lui ouvrir. Un cas de
     * `DroitsDeLAdministrateurTest` balaie `app/` et énumère les appels
     * légitimes - tous dans la réserve ci-dessus.
     *
     * On garde le repli spatie (`administrator` / `manage_colloques`) : ce rôle
     * hérité vaut toujours super administrateur.
     */
    public function isColloqueSuperAdmin(): bool
    {
        return $this->access_level === self::ACCESS_SUPER_ADMIN
            || $this->hasRole('administrator')
            || $this->getAllPermissions()->contains('name', 'manage_colloques');
    }

    /**
     * ⚠️⚠️ **CE QUI OUVRE TOUTE LA PLATEFORME.** Administrateur **ou** super
     * administrateur : les deux passent partout, et c'est ce prédicat que
     * doivent interroger le `Gate::before`, `EnsureEditionAccess` et les cinq
     * portes d'accès aux événements ci-dessous. L'administrateur n'a donc plus
     * besoin d'être affecté à un événement pour l'ouvrir : il crée les
     * événements et y attache SON gestionnaire, il ne devient pas l'équipe.
     */
    /**
     * Peut modifier la MATRICE DES PERMISSIONS.
     *
     * ⚠️ C'est l'une des rares choses qui restent au super administrateur : la
     * matrice décide de ce que chaque fonction peut faire, donc l'ouvrir à
     * l'administrateur lui permettrait de s'accorder lui-même le cran qui lui
     * manque. La question se pose ICI, en termes de métier - un appel direct à
     * `isColloqueSuperAdmin()` depuis un contrôleur est le réflexe qui a
     * refermé, au cas par cas, ce que le modèle venait d'ouvrir.
     */
    public function peutReglerLaMatrice(): bool
    {
        return $this->isColloqueSuperAdmin();
    }

    public function isColloqueAdmin(): bool
    {
        return $this->isColloqueSuperAdmin() || $this->access_level === self::ACCESS_COLLOQUE_ADMIN;
    }

    /** Peut créer un nouvel événement (administrateur ou super administrateur). */
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
        return $this->isColloqueAdmin() || $this->rolesOnEdition($event) !== [];
    }

    /**
     * Capacités effectives de l'utilisateur SUR un événement (union des matrices
     * de ses fonctions ; toutes les capacités pour un administrateur).
     *
     * @return list<string>
     */
    public function capabilitiesOnEdition(Event|int $event): array
    {
        if ($this->isColloqueAdmin()) {
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
        return $this->isColloqueAdmin()
            || in_array($capability, $this->capabilitiesOnEdition($event), true);
    }

    /**
     * Identifiants des événements accessibles à l'utilisateur (tous s'il est
     * administrateur ; sinon ceux où il est affecté - filtrés par capacité si
     * fournie).
     *
     * @return list<int>
     */
    public function accessibleEditionIds(?string $capability = null): array
    {
        if ($this->isColloqueAdmin()) {
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
        return $this->isColloqueAdmin() || $this->accessibleEditionIds($capability) !== [];
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
