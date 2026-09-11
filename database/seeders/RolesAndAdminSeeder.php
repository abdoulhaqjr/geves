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
 * Rôles métier UK Colloques, permissions (matrice d'accès du back-office) et
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

        // Administrateur par défaut (dev). À changer en production.
        $user = User::firstOrCreate(
            ['email' => 'admin@univkara.tg'],
            ['name' => 'Administrateur', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        if (! $user->hasRole('administrator')) {
            $user->assignRole('administrator');
        }
        if ($user->access_level !== User::ACCESS_SUPER_ADMIN) {
            $user->forceFill(['access_level' => User::ACCESS_SUPER_ADMIN])->save();
        }
    }
}
