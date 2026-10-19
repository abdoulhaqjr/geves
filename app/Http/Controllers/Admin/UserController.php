<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountResetPassword;
use App\Support\ListSort;
use App\Support\StaffRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    public function index(Request $request): View
    {
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('search', ''));
        $sort = ListSort::resolve($request, ['name', 'email', 'last_login_at', 'is_active'], 'name', 'asc');

        $users = User::query()
            ->with('roles')
            ->when($role !== '', fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
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

        // Comptes par rôle GLOBAL (pour les indicateurs + l'onglet Rôles).
        // whereHas plutôt que le scope role() de spatie : renvoie 0 sans lever
        // RoleDoesNotExist si le rôle n'a pas encore été créé (install fraîche).
        $countsByRole = collect(array_keys(StaffRoles::ROLES))
            ->mapWithKeys(fn ($r) => [$r => User::whereHas('roles', fn ($q) => $q->where('name', $r))->count()]);

        // ⚠️ Et les affectations PAR ÉVÉNEMENT, qui sont la vraie vie des
        // fonctions d'équipe : `$countsByRole` ne voit que les rôles globaux, si
        // bien que l'onglet Rôles annonçait « 0 utilisateur » pour un secrétariat
        // et une comptabilité pourtant à l'œuvre sur un colloque.
        // On compte les PERSONNES (user_id distincts), pas les lignes : tenir la
        // même fonction sur deux événements ne fait pas deux personnes.
        $parEvenement = DB::table('event_user')
            ->selectRaw('role, count(distinct user_id) as personnes, count(distinct event_id) as evenements')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

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
        $support = collect(StaffRoles::ROLES)
            ->filter(fn ($m) => $m['category'] === 'Appui')
            ->keys()
            ->sum(fn ($r) => $countsByRole[$r] ?? 0);

        return view('admin.users.index', [
            'users' => $users,
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
                'total' => User::count(),
                'admins' => $countsByRole['administrator'] ?? 0,
                'reviewers' => $countsByRole['reviewer'] ?? 0,
                'organizers' => $countsByRole['organizer'] ?? 0,
                'support' => $support,
            ],
        ]);
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
     * visible, d'un coup d'œil, qu'« Administrateur colloque » est un rôle de
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

        return redirect()->route('admin.users.index', ['tab' => 'access'])
            ->with('ukc_status', 'Matrice d’accès mise à jour.');
    }

    public function create(): View
    {
        return view('admin.users.form', $this->formData(new User(['is_active' => true]), []));
    }

    /**
     * Ce que la fiche d'un compte a besoin de montrer.
     *
     * ⚠️ **Les rôles de cette page sont GLOBAUX, et un seul ouvre quelque chose.**
     * Les douze cases servaient le même traitement à tout le monde, sous la
     * promesse « la matrice d'accès en découle ». C'est faux pour onze d'entre
     * elles : les capacités d'une fonction d'équipe ne sont lues que par
     * `edition.cap:` → `canOnEdition()`, qui part de `event_user` - donc d'une
     * affectation à UN événement. Les trois gardes `can:` du back-office
     * (Réglages, Utilisateurs & rôles, Témoignages) portent sur des permissions
     * que seul « Administrateur colloque » détient. Cocher « Secrétariat » ici
     * n'ouvre donc **aucune** page : ça pose une étiquette, et c'est tout ce que
     * l'écran doit promettre. {@see affectationsDe()} montre, à côté, l'accès
     * réel - celui qu'on venait chercher.
     *
     * @param  list<string>  $assignedRoles
     * @return array<string, mixed>
     */
    private function formData(User $user, array $assignedRoles): array
    {
        // Les fonctions rangées par famille, chacune avec sa description et le
        // nombre de capacités qu'elle porte : la page les alignait sans un mot.
        $groupes = [];
        foreach (StaffRoles::ROLES as $name => $meta) {
            $groupes[$meta['category']][$name] = [
                'label' => $meta['label'],
                'description' => $meta['description'],
                'caps' => count($meta['permissions']),
                'pill' => $meta['pill'],
            ];
        }

        return [
            'user' => $user,
            'assignedRoles' => $assignedRoles,
            'roleOptions' => StaffRoles::options(),
            'roleGroups' => $groupes,
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
        $user->syncRoles($request->input('roles', []));

        if ($request->boolean('send_verification')) {
            $user->sendStaffEmailVerificationNotification();
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('ukc_status', 'Utilisateur créé.'.($request->boolean('send_verification') ? ' E-mail de vérification envoyé.' : ''));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', $this->formData($user, $user->roles->pluck('name')->all()));
    }

    public function update(User $user, Request $request): RedirectResponse
    {
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
        $user->syncRoles($request->input('roles', []));

        return redirect()->route('admin.users.edit', $user)->with('ukc_status', 'Utilisateur mis à jour.');
    }

    /**
     * Ce qui fermerait le back-office à clé, et pourquoi on le refuse.
     *
     * ⚠️⚠️ **`destroy()` gardait la porte, `update()` gardait la fenêtre ouverte.**
     * La suppression interdit depuis toujours d'effacer son propre compte et le
     * dernier administrateur ; l'enregistrement de la fiche, lui, ne vérifiait
     * **rien** - alors qu'il mène exactement au même endroit par un autre
     * chemin : décocher « Administrateur colloque » sur le dernier
     * administrateur, rétrograder le dernier super-administrateur, ou se
     * désactiver soi-même. Après quoi plus personne n'ouvre
     * `admin/utilisateurs` pour revenir en arrière, et la réparation se fait en
     * base.
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
        $roles = (array) $request->input('roles', []);
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
            if ($user->hasRole('administrator') && ! in_array('administrator', $roles, true)) {
                return 'Vous ne pouvez pas retirer votre propre rôle d’administrateur : demandez-le à un autre administrateur.';
            }
        }

        if ($user->hasRole('administrator')
            && ! in_array('administrator', $roles, true)
            && User::role('administrator')->count() <= 1) {
            return 'Impossible de retirer le rôle au dernier administrateur.';
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
        if ($user->id === Auth::id()) {
            return back()->with('ukc_error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        // Ne pas retirer le dernier administrateur.
        if ($user->hasRole('administrator') && User::role('administrator')->count() <= 1) {
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
            'access_level' => ['required', Rule::in(array_keys(User::accessLevels()))],
            'roles' => ['array'],
            'roles.*' => [Rule::in(array_keys(StaffRoles::ROLES))],
        ]);
    }
}
