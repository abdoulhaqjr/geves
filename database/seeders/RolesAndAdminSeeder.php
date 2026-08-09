<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Rôles, permission `manage_colloques` et compte administrateur par défaut du
 * back-office.
 */
class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        $manage = Permission::firstOrCreate([
            'name' => 'manage_colloques',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        $editor = Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

        $admin->givePermissionTo($manage);
        $editor->givePermissionTo($manage);

        // Administrateur par défaut (dev). À changer en production.
        $user = User::firstOrCreate(
            ['email' => 'admin@univkara.tg'],
            ['name' => 'Administrateur', 'password' => Hash::make('password')]
        );

        if (! $user->hasRole('administrator')) {
            $user->assignRole('administrator');
        }
    }
}
