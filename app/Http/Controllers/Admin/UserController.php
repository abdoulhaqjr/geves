<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountResetPassword;
use App\Support\IpCountry;
use App\Support\ListSort;
use App\Support\StaffRolePermissions;
use App\Support\StaffRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Utilisateurs du back-office (Blade, fidèle au mockup admin-users) : liste des
 * comptes staff avec rôles métier UKC, statut de vérification et activité de
 * connexion ; onglets Rôles & Accès (matrice de permissions, données réelles
 * spatie). Actions : renvoi du mail de vérification, suppression, et Modifier
 * → page détail (pas de modal, cf. guide §5.1).
 */
class UserController extends Controller
{
    public function index(Request $request, IpCountry $ipCountry): View
    {
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $sort = ListSort::resolve($request, ['name', 'email', 'last_login_at', 'is_active'], 'name', 'asc');

        // ⚠️⚠️ Un SUPER ADMINISTRATEUR ne se montre qu'à un super administrateur,
        // liste ET compteurs ({@see User::scopeVisiblesPar()}). Un compte qu'on
        // laisse apparaître dans un total est un compte dont on apprend
        // l'existence.
        $moi = $this->moi();

        $users = User::query()
            ->visiblesPar($moi)
            ->when($role !== '', function ($q) use ($role) {
                if ($role === 'administrator') {
                    $q->whereHas('roles', fn ($r) => $r->where('name', $role));
                } elseif ($role === 'reviewer') {
                    $q->whereIn('users.id', DB::table('participants')->where('role', 'reviewer')->select('user_id'));
                } elseif (array_key_exists($role, StaffRoles::teamRoles())) {
                    $q->whereIn('users.id', DB::table('event_user')->where('role', $role)->select('user_id'));
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($status !== '', fn ($q) => $q->where('is_active', $status === 'active'))
            ->when($search !== '', function ($q) use ($search) {
                $term = "%{$search}%";
                $q->where(fn ($u) => $u->where('name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('last_login_ip', 'like', $term));
            })
            ->orderBy($sort['sort'], $sort['dir'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $countriesByIp = $ipCountry->namesFor($users->getCollection()->pluck('last_login_ip'));

        // ⚠️ Un pays absent a TROIS causes, et la page n'en distinguait aucune :
        // la base locale n'est pas installée, l'adresse est privée (poste de
        // développement, mandataire non déclaré), ou la plage est inconnue de
        // l'édition gratuite. Des tirets partout se lisaient comme une panne.
        $geoip = [
            'resolvable' => IpCountry::resolvable(),
            'base' => IpCountry::databaseInstalled(),
            // ⚠️ Deux manques, deux gestes : la base se télécharge, la
            // bibliothèque s'installe. Un seul message enverrait la moitié des
            // cas vers la mauvaise commande.
            'reader' => IpCountry::readerAvailable(),
        ];

        // Comptes par rôle GLOBAL (pour les indicateurs + l'onglet Rôles).
        // whereHas plutôt que le scope role() de spatie : renvoie 0 sans lever
        // RoleDoesNotExist si le rôle n'a pas encore été créé (install fraîche).
        $countsByRole = collect(array_keys(StaffRoles::ROLES))
            ->mapWithKeys(fn ($r) => [$r => User::query()->visiblesPar($moi)
                ->whereHas('roles', fn ($q) => $q->where('name', $r))->count()]);

        // ⚠️ Et les affectations PAR ÉVÉNEMENT, qui sont la vraie vie des
        // fonctions d'équipe : `$countsByRole` ne voit que les rôles globaux, si
        // bien que l'onglet Rôles annonçait « 0 utilisateur » pour un secrétariat
        // et une comptabilité pourtant à l'œuvre sur un colloque.
        // On compte les PERSONNES (user_id distincts), pas les lignes : tenir la
        // même fonction sur deux événements ne fait pas deux personnes.
        $parEvenement = DB::table('event_user')
            ->whereIn('user_id', User::query()->visiblesPar($moi)->select('id'))
            ->selectRaw('role, count(distinct user_id) as personnes, count(distinct event_id) as evenements')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        $evaluationUsage = DB::table('participants')->where('role', 'reviewer')
            ->whereIn('user_id', User::query()->visiblesPar($moi)->select('users.id'))
            ->selectRaw('count(distinct user_id) as personnes, count(distinct event_id) as evenements')
            ->first();
        if ($evaluationUsage && $evaluationUsage->personnes > 0) {
            $parEvenement->put('reviewer', $evaluationUsage);
        }

        // Permissions RÉELLES par rôle (spatie) - source de la matrice d'accès éditable.
        $rolePermissions = Role::query()->where('guard_name', 'web')
            ->whereIn('name', array_keys(StaffRoles::ROLES))
            ->with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $r) => [$r->name => $r->permissions->pluck('name')->all()]);

        // ⚠️ La famille s'appelle « Appui » depuis le renommage : « Système » /
        // « Support » entrait en collision avec les TROIS rôles système du modèle
        // (super-admin, admin, utilisateur), qui n'ont rien à voir avec ce
        // classement des fonctions d'équipe.
        $supportRoles = collect(StaffRoles::ROLES)
            ->filter(fn ($m) => $m['category'] === 'Appui')
            ->keys()->all();

        $visibleIds = User::query()->visiblesPar($moi)->select('users.id');
        $eventUsers = DB::table('event_user')->whereIn('user_id', $visibleIds);

        return view('admin.users.index', [
            'users' => $users,
            'countriesByIp' => $countriesByIp,
            'geoip' => $geoip,
            'role' => $role,
            'status' => $status,
            'search' => $search,
            'sort' => $sort['sort'],
            'dir' => $sort['dir'],
            'roleOptions' => StaffRoles::options(),
            'roles' => StaffRoles::ROLES,
            'permissions' => StaffRoles::PERMISSIONS,
            'countsByRole' => $countsByRole,
            'roleUsage' => $this->roleUsageLines($parEvenement->all(), $countsByRole->all(), $rolePermissions->all()),
            'rolePermissions' => $rolePermissions,
            'stats' => [
                'total' => User::query()->visiblesPar($moi)->count(),
                'online' => $this->onlineCount($moi),
                'admins' => User::query()->visiblesPar($moi)
                    ->where(fn ($q) => $q->where('access_level', User::ACCESS_COLLOQUE_ADMIN)
                        ->orWhereIn('users.id', User::query()->superAdministrateurs()->select('users.id')))
                    ->count(),
                'reviewers' => (int) ($evaluationUsage->personnes ?? 0),
                'organizers' => (clone $eventUsers)->where('role', 'organizer')->distinct()->count('user_id'),
                'support' => (clone $eventUsers)->whereIn('role', $supportRoles)->distinct()->count('user_id'),
            ],
        ]);
    }

    /** Personnes distinctes dont une session authentifiée a été active récemment. */
    private function onlineCount(?User $viewer): ?int
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        $window = min(5 * 60, (int) config('session.lifetime', 120) * 60);

        return User::query()
            ->visiblesPar($viewer)
            ->where('is_active', true)
            ->whereIn('users.id', DB::table(config('session.table', 'sessions'))
                ->where('last_activity', '>=', now()->timestamp - $window)
                ->whereNotNull('user_id')
                ->select('user_id'))
            ->count();
    }

    /** Rafraîchissement du compteur sans recharger les filtres du tableau. */
    public function online(): JsonResponse
    {
        return response()->json(['count' => $this->onlineCount($this->moi())]);
    }

    /**
     * La ligne de compteurs d'une fonction, dans l'onglet Rôles.
     *
     * ⚠️ **Les personnes AFFECTÉES d'abord.** Le compteur ne lisait que les rôles
     * globaux (spatie) alors qu'une fonction d'équipe se confie par événement :
     * l'écran annonçait « 0 utilisateur » pour un secrétariat et une comptabilité
     * pourtant à l'œuvre sur un colloque. Le chiffre n'était pas faux, il
     * répondait à une question que personne ne pose devant cet écran.
     *
     * ⚠️ **Le rôle global n'est mentionné QUE s'il y en a** : c'est ce qui rend
     * visible, d'un coup d'œil, qu'« Administrateur (rôle hérité) » est un rôle de
     * plateforme et non une fonction d'événement.
     *
     * Assemblé ici et non dans le gabarit : un bloc `@php … @endphp` y casserait
     * la compilation, le fichier contenant déjà un `@php(...)` en ligne.
     *
     * ⚠️ Des TABLEAUX et non des `Collection` : les génériques de Collection ne
     * sont pas covariants, si bien qu'une collection issue de `keyBy()` ou de
     * `pluck()` ne satisfait jamais un type de paramètre plus précis. Le tableau,
     * lui, est covariant - et cette méthode n'a besoin de rien d'autre.
     *
     * @param  array<array-key, object>  $parEvenement
     * @param  array<string, int>  $countsByRole
     * @param  array<string, array<mixed>>  $rolePermissions
     * @return array<string, string>
     */
    private function roleUsageLines(array $parEvenement, array $countsByRole, array $rolePermissions): array
    {
        $lignes = [];

        foreach (array_keys(StaffRoles::ROLES) as $name) {
            $usage = $parEvenement[$name] ?? null;
            $global = (int) ($countsByRole[$name] ?? 0);
            $perms = count(array_intersect($rolePermissions[$name] ?? [], array_keys(StaffRoles::PERMISSIONS)));

            $bits = [$usage === null
                ? 'aucune affectation'
                : trans_choice(':count personne|:count personnes', (int) $usage->personnes, ['count' => $usage->personnes])
                    .' sur '.trans_choice(':count événement|:count événements', (int) $usage->evenements, ['count' => $usage->evenements]),
            ];

            if ($global > 0) {
                $bits[] = trans_choice(':count rôle global|:count rôles globaux', $global, ['count' => $global]);
            }

            $bits[] = trans_choice(':count permission|:count permissions', $perms, ['count' => $perms]);

            $lignes[$name] = implode(' - ', $bits);
        }

        return $lignes;
    }

    /**
     * Met à jour la matrice d'accès : synchronise les permissions granulaires de
     * chaque rôle UKC depuis les cases cochées. L'administrateur conserve toutes
     * les permissions ; la porte d'entrée `manage_colloques` des rôles
     * « Organisation » est préservée (hors matrice).
     */
    public function updateAccess(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->peutReglerLaMatrice(), 403);

        $validPerms = array_keys(StaffRoles::PERMISSIONS);

        foreach (StaffRoles::ROLES as $roleName => $meta) {
            $role = Role::where('guard_name', 'web')->where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            if ($roleName === 'administrator') {
                // L'administrateur garde toutes les permissions + la super-capacité.
                $perms = array_merge($validPerms, ['manage_colloques']);
            } else {
                $submitted = (array) $request->input("matrix.$roleName", []);
                $perms = array_values(array_intersect($submitted, $validPerms));
            }

            $role->syncPermissions($perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(StaffRolePermissions::class)->forget();

        return redirect()->route('admin.users.index', ['tab' => 'access'])
            ->with('ukc_status', 'Matrice d’accès mise à jour.');
    }

    public function create(): View
    {
        return view('admin.users.form', $this->formData(new User(['is_active' => true])));
    }

    /**
     * Ce que la fiche d'un compte a besoin de montrer.
     *
     * ⚠️⚠️ **UN COMPTE N'A PAS DE RÔLE GLOBAL - il a un NIVEAU D'ACCÈS.** La
     * fiche proposait les douze fonctions de `StaffRoles` en cases à cocher, à
     * l'échelle de la plateforme. C'est le modèle même du projet qu'elle
     * contredisait : il n'y a que **trois** niveaux globaux
     * ({@see User::accessLevels()}), et tout le reste - gestionnaire,
     * co-gestionnaire, comités, secrétariat, comptabilité, accueil - se confie
     * **par événement**, dans `event_user`. Les douze cases ne faisaient donc
     * rien d'autre que poser une étiquette : les capacités d'une fonction ne
     * sont lues que par `edition.cap:` → `canOnEdition()`, qui part de
     * l'affectation. L'admin crée l'événement et y nomme son gestionnaire ;
     * c'est le gestionnaire qui forme l'équipe. {@see affectationsDe()} montre
     * le résultat, en lecture seule.
     *
     * @return array<string, mixed>
     */
    private function formData(User $user): array
    {
        return [
            'user' => $user,
            // ⚠️ Pas `accessLevels()` : « Super administrateur » ne se propose
            // qu'à un super administrateur ({@see User::niveauxAttribuablesPar()}).
            // La vue ne dresse donc plus la liste elle-même.
            'accessLevels' => User::niveauxAttribuablesPar($this->moi()),
            'accessHints' => User::accessLevelHints(),
            'affectations' => $this->affectationsDe($user),
        ];
    }

    /**
     * Les colloques où ce compte travaille vraiment, et à quel titre.
     *
     * ⚠️ C'est l'information que la fiche ne portait **pas du tout** : on y
     * réglait des rôles de plateforme sans jamais voir à quels événements le
     * compte est rattaché - c'est-à-dire sans voir ce à quoi il accède. En
     * lecture seule : une affectation se confie depuis l'onglet Équipe de
     * l'événement, qui est aussi l'écran où l'on sait qui d'autre s'y trouve.
     *
     * @return list<array{event_id: int, title: string, year: int|null, role: string, label: string, pill: string, caps: int}>
     */
    private function affectationsDe(User $user): array
    {
        if (! $user->exists) {
            return [];
        }

        return DB::table('event_user')
            ->join('events', 'events.id', '=', 'event_user.event_id')
            ->where('event_user.user_id', $user->id)
            ->orderByDesc('events.year')
            ->orderBy('events.title')
            ->get(['events.id', 'events.title', 'events.year', 'event_user.role'])
            ->map(fn ($r) => [
                'event_id' => (int) $r->id,
                'title' => (string) $r->title,
                'year' => $r->year === null ? null : (int) $r->year,
                'role' => (string) $r->role,
                'label' => StaffRoles::label((string) $r->role),
                'pill' => StaffRoles::pill((string) $r->role),
                'caps' => count(StaffRoles::permissionsFor((string) $r->role)),
            ])
            ->values()
            ->all();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateUser($request, null);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password'] ?? Str::password(16)),
            'is_active' => $request->boolean('is_active'),
            'access_level' => $data['access_level'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->boolean('send_verification')) {
            $user->sendStaffEmailVerificationNotification();
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('ukc_status', 'Utilisateur créé.'.($request->boolean('send_verification') ? ' E-mail de vérification envoyé.' : ''));
    }

    /**
     * ⚠️⚠️ **MASQUER UNE LIGNE NE FERME PAS UNE PORTE.** Le compte d'un super
     * administrateur disparaît de la liste pour qui n'en est pas un ; sans cette
     * garde, son identifiant suffisait encore à ouvrir sa fiche, à lui envoyer
     * une réinitialisation de mot de passe, à le désactiver ou à le supprimer.
     * Elle est posée sur les CINQ actions qui prennent un `{user}`.
     *
     * ⚠️ **404, pas 403** : un 403 confirmerait que le compte existe, ce que
     * cette garde sert précisément à taire.
     */
    private function garde(User $user): void
    {
        abort_unless(User::voitLeCompte($this->moi(), $user), 404);
    }

    /** Le membre du staff connecté, ou null - jamais un autre type d'authentifiable. */
    private function moi(): ?User
    {
        $moi = Auth::user();

        return $moi instanceof User ? $moi : null;
    }

    public function edit(User $user): View
    {
        $this->garde($user);

        return view('admin.users.form', $this->formData($user));
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        $this->garde($user);
        $data = $this->validateUser($request, $user);

        if ($refus = $this->refusDeFermeture($user, $request, $data)) {
            return back()->withInput()->with('ukc_error', $refus);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active'),
            'access_level' => $data['access_level'],
            'notes' => $data['notes'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        // ⚠️ Aucun `syncRoles()` ici, et ce n'est pas un oubli : cette fiche ne
        // distribue plus de rôle global (cf. formData()). Les rôles déjà posés
        // en base sont laissés en place - ils alimentent encore les pastilles et
        // le filtre de la liste - mais plus rien ne les crée depuis cet écran.

        return redirect()->route('admin.users.edit', $user)->with('ukc_status', 'Utilisateur mis à jour.');
    }

    /**
     * Ce qui fermerait le back-office à clé, et pourquoi on le refuse.
     *
     * ⚠️⚠️ **`destroy()` gardait la porte, `update()` gardait la fenêtre ouverte.**
     * La suppression interdit depuis toujours d'effacer son propre compte et le
     * dernier administrateur ; l'enregistrement de la fiche, lui, ne vérifiait
     * **rien** - alors qu'il mène exactement au même endroit par un autre
     * chemin : rétrograder le dernier super-administrateur, ou se désactiver
     * soi-même. Après quoi plus personne n'ouvre `admin/utilisateurs` pour
     * revenir en arrière, et la réparation se fait en base.
     *
     * ⚠️ **Le niveau d'accès est la SEULE chose à garder ici**, depuis que la
     * fiche ne distribue plus de rôle global : c'est lui qui porte la totalité
     * du pouvoir de plateforme, et `Gate::before` en découle.
     *
     * ⚠️ **Sur soi-même, c'est interdit même quand d'autres administrateurs
     * existent** : la suppression de son propre compte l'est déjà sans
     * condition, et on ne se retire pas ses propres accès - un collègue le
     * fait, en connaissance de cause.
     *
     * @param  array<string, mixed>  $data
     */
    private function refusDeFermeture(User $user, Request $request, array $data): ?string
    {
        $niveau = (string) $data['access_level'];

        if ($user->id === Auth::id()) {
            if (! $request->boolean('is_active')) {
                return 'Vous ne pouvez pas désactiver votre propre compte : demandez-le à un autre administrateur.';
            }
            // ⚠️ Le défaut d'`access_level` vit en BASE : un compte peut porter
            // `null`. Lui en poser un n'est pas se retirer quoi que ce soit.
            if ($user->access_level !== null && $niveau !== $user->access_level) {
                return 'Vous ne pouvez pas changer votre propre niveau d’accès : demandez-le à un autre administrateur.';
            }
        }

        if ($user->access_level === User::ACCESS_SUPER_ADMIN
            && $niveau !== User::ACCESS_SUPER_ADMIN
            && User::where('access_level', User::ACCESS_SUPER_ADMIN)->count() <= 1) {
            return 'Impossible de rétrograder le dernier super administrateur.';
        }

        return null;
    }

    public function resendVerification(User $user): RedirectResponse
    {
        $this->garde($user);
        if ($user->hasVerifiedEmail()) {
            return back()->with('ukc_error', 'Cet utilisateur a déjà vérifié son adresse.');
        }
        $user->sendStaffEmailVerificationNotification();

        return back()->with('ukc_status', 'E-mail de vérification renvoyé à '.$user->email.'.');
    }

    /**
     * Envoie à l'utilisateur un e-mail de réinitialisation de mot de passe.
     * Passe par le courtier « users » (= User::sendPasswordResetNotification),
     * donc le message rédigé/localisé {@see AccountResetPassword}.
     * Comptes unifiés : vaut pour le staff comme pour les participants.
     */
    public function sendPasswordReset(User $user): RedirectResponse
    {
        $this->garde($user);
        $status = Password::broker('users')->sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('ukc_status', 'E-mail de réinitialisation du mot de passe envoyé à '.$user->email.'.');
        }

        $message = $status === Password::RESET_THROTTLED
            ? 'Un e-mail de réinitialisation a déjà été envoyé récemment ; patientez avant de réessayer.'
            : 'Impossible d’envoyer l’e-mail de réinitialisation à '.$user->email.'.';

        return back()->with('ukc_error', $message);
    }

    /**
     * Suppression d'un compte : SoftDeletes (réversible), confirmée par la saisie
     * du mot « Supprimer » ET du mot de passe de l'administrateur connecté (même
     * esprit que la suppression de compte de Laravel). Les messages passent par le
     * flash (toast) pour s'afficher aussi bien depuis la liste que depuis la fiche.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->garde($user);
        if ($user->id === Auth::id()) {
            return back()->with('ukc_error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        // Ne pas retirer le dernier administrateur.
        if (($user->access_level === User::ACCESS_SUPER_ADMIN
                && User::where('access_level', User::ACCESS_SUPER_ADMIN)->count() <= 1)
            || (User::query()->superAdministrateurs()->whereKey($user->id)->exists()
                && User::query()->superAdministrateurs()->count() <= 1)) {
            return back()->with('ukc_error', 'Impossible de supprimer le dernier administrateur.');
        }

        if ($request->input('confirm') !== 'Supprimer') {
            return back()->with('ukc_error', 'Saisissez exactement « Supprimer » pour confirmer la suppression.');
        }

        $admin = Auth::user();
        if (! $admin instanceof User || ! Hash::check((string) $request->input('password'), (string) $admin->password)) {
            return back()->with('ukc_error', 'Mot de passe incorrect : la suppression a été annulée.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('ukc_status', 'Utilisateur supprimé.');
    }

    /** @return array<string, mixed> */
    private function validateUser(Request $request, ?User $user): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'nullable', 'string', 'min:8'],
            'notes' => ['nullable', 'string', 'max:500'],
            // ⚠️ Plus de règle `roles` : la fiche n'en poste plus, et un champ
            // qu'on valide est un champ qu'on promet d'accepter.
            //
            // ⚠️⚠️ **ET LE NIVEAU SE VALIDE SUR CE QUE L'AUTEUR PEUT ATTRIBUER**,
            // pas sur la liste entière : seul un super administrateur en nomme
            // un autre. La fiche ne propose déjà plus le choix à qui n'y a pas
            // droit - mais retirer une case ne ferme aucune porte, et c'est ici
            // qu'un `access_level=super_admin` posté à la main s'arrête.
            'access_level' => ['required', Rule::in(array_keys(User::niveauxAttribuablesPar($this->moi())))],
        ]);
    }
}
