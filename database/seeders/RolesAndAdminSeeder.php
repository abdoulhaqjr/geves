<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles métier UK Colloque, permissions (matrice d'accès du back-office) et
 * compte administrateur par défaut. Les rôles/permissions viennent de la source
 * unique App\Support\StaffRoles (guard « web »). La permission historique
 * `manage_colloques` reste la porte d'entrée du back-office (rôles « Système »).
 */
class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Porte d'entrée historique du back-office.
        $manage = Permission::firstOrCreate(['name' => 'manage_colloques', 'guard_name' => 'web']);

        // Permissions granulaires (matrice).
        foreach (array_keys(StaffRoles::PERMISSIONS) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Rôles UKC + affectation des permissions granulaires (matrice d'accès).
        foreach (StaffRoles::ROLES as $name => $meta) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($meta['permissions']);

            // Seul l'administrateur porte la super-capacité `manage_colloques`
            // (bypass Gate::before). Les autres rôles sont gouvernés par la matrice.
            if ($name === 'administrator') {
                $role->givePermissionTo($manage);
            }
        }

        // Rôle legacy conservé (compat, accès complet).
        Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web'])->givePermissionTo($manage);

        // ⚠️ Ce seeder tourne AUSSI en production : c'est lui qui installe les
        // rôles et le super-administrateur. Il posait le mot de passe
        // « password », donc une mise en ligne ouvrait un compte à tous les
        // pouvoirs avec un mot de passe public. En production, le mot de passe
        // vient de la configuration et son absence ARRÊTE le déploiement :
        // mieux vaut un déploiement qui échoue qu'un site ouvert.
        //
        // ⚠️ `config()`, PAS `env()`. Un `env()` appelé hors d'un fichier de
        // configuration rend `null` dès que la configuration est en cache
        // (`php artisan config:cache`), ce qui est la règle en production :
        // le seeder refusait un mot de passe pourtant bien renseigné dans le
        // `.env`. Vu sur le premier déploiement.
        $email = (string) (config('super-admin.email') ?: 'admin@univkara.tg');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $motDePasse = (string) (config('super-admin.password') ?: '');

            if ($motDePasse === '') {
                if (app()->isProduction()) {
                    throw new \RuntimeException(
                        'ADMIN_PASSWORD est vide : refus de créer le super-administrateur '
                        .'avec un mot de passe par défaut en production. Renseignez '
                        .'ADMIN_EMAIL et ADMIN_PASSWORD dans .env, puis lancez '
                        .'`php artisan config:clear` AVANT de relancer le seeder : '
                        .'une configuration en cache ne relit pas le fichier .env.'
                    );
                }
                $motDePasse = 'password';
            }

            $user = User::create([
                'email' => $email,
                'name' => (string) (config('super-admin.name') ?: 'Administrateur'),
                'password' => Hash::make($motDePasse),
                'email_verified_at' => now(),
            ]);
        }

        if (! $user->hasRole('administrator')) {
            $user->assignRole('administrator');
        }
        if ($user->access_level !== User::ACCESS_SUPER_ADMIN) {
            $user->forceFill(['access_level' => User::ACCESS_SUPER_ADMIN])->save();
        }
    }
}
