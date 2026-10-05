<?php

use App\Support\StaffRoles;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Capacité dédiée « project » (gestion de projet de l'événement), auparavant
 * portée par « view_dashboard ». Crée la permission (guard web) et l'attribue,
 * de façon additive, aux rôles qui la possèdent dans App\Support\StaffRoles.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'project', 'guard_name' => 'web']);

        foreach (StaffRoles::ROLES as $name => $meta) {
            if (! in_array('project', $meta['permissions'], true)) {
                continue;
            }
            $role = Role::where(['name' => $name, 'guard_name' => 'web'])->first();
            $role?->givePermissionTo('project');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where(['name' => 'project', 'guard_name' => 'web'])->delete();
    }
};
