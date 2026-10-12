<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountResetPassword;
use App\Support\ListSort;
use App\Support\StaffRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                    ->orWhere('email', 'like', $term)
                    ->orWhere('last_login_ip', 'like', $term));
            })
            ->orderBy($sort['sort'], $sort['dir'])
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Comptes par rôle (pour les indicateurs + l'onglet Rôles).
        // whereHas plutôt que le scope role() de spatie : renvoie 0 sans lever
        // RoleDoesNotExist si le rôle n'a pas encore été créé (install fraîche).
        $countsByRole = collect(array_keys(StaffRoles::ROLES))
            ->mapWithKeys(fn ($r) => [$r => User::whereHas('roles', fn ($q) => $q->where('name', $r))->count()]);

        // Permissions RÉELLES par rôle (spatie) - source de la matrice d'accès éditable.
        $rolePermissions = Role::query()->where('guard_name', 'web')
            ->whereIn('name', array_keys(StaffRoles::ROLES))
            ->with('permissions')
            ->get()
            ->mapWithKeys(fn (Role $r) => [$r->name => $r->permissions->pluck('name')->all()]);

        $support = collect(StaffRoles::ROLES)
            ->filter(fn ($m) => $m['category'] === 'Support')
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
     * Met à jour la matrice d'accès : synchronise les permissions granulaires de
     * chaque rôle UKC depuis les cases cochées. L'administrateur conserve toutes
     * les permissions ; la porte d'entrée `manage_colloques` des rôles « Système »
     * est préservée (hors matrice).
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
        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'assignedRoles' => [],
            'roleOptions' => StaffRoles::options(),
        ]);
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
        return view('admin.users.form', [
            'user' => $user,
            'assignedRoles' => $user->roles->pluck('name')->all(),
            'roleOptions' => StaffRoles::options(),
        ]);
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

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

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('ukc_error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        // Ne pas retirer le dernier administrateur.
        if ($user->hasRole('administrator') && User::role('administrator')->count() <= 1) {
            return back()->with('ukc_error', 'Impossible de supprimer le dernier administrateur.');
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
