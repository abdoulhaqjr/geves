<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte comptes & rôles — Phase 3 : niveau d'accès global (3 niveaux).
 *
 *  - super_admin     : toute la plateforme, tous les colloques ;
 *  - colloque_admin  : peut créer des colloques et gérer/co-gérer les siens ;
 *  - user (défaut)   : aucun accès global, agit selon ses rôles par colloque.
 *
 * Remplace à terme le rôle spatie unique `administrator`. Backfill : les
 * utilisateurs qui portent ce rôle (ou la permission `manage_colloques`)
 * deviennent super_admin. Réversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('access_level', 20)->default('user')->after('is_active')->index();
        });

        // Super-admins existants = rôle `administrator` ou permission `manage_colloques`.
        $ids = collect();
        $adminRoleIds = DB::table('roles')->where('name', 'administrator')->pluck('id');
        if ($adminRoleIds->isNotEmpty()) {
            $ids = $ids->merge(
                DB::table('model_has_roles')
                    ->whereIn('role_id', $adminRoleIds)
                    ->where('model_type', User::class)
                    ->pluck('model_id')
            );
        }
        $permId = DB::table('permissions')->where('name', 'manage_colloques')->value('id');
        if ($permId) {
            $ids = $ids->merge(
                DB::table('model_has_permissions')
                    ->where('permission_id', $permId)
                    ->where('model_type', User::class)
                    ->pluck('model_id')
            );
        }

        if ($ids->isNotEmpty()) {
            DB::table('users')->whereIn('id', $ids->unique()->all())->update(['access_level' => 'super_admin']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_access_level_index');
            $table->dropColumn('access_level');
        });
    }
};
