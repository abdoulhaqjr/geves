<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retire le rôle legacy `editor`.
 *
 * Il portait la super-capacité `manage_colloques`, qui court-circuite
 * `Gate::before` et ouvre donc TOUT le back-office. Mais il n'était pas dans
 * `StaffRoles::ROLES` : il n'apparaissait ni dans l'onglet Rôles, ni dans la
 * matrice d'accès. Un rôle à tous les pouvoirs, invisible des écrans qui servent
 * justement à surveiller les pouvoirs.
 *
 * Rien ne l'employait : zéro utilisateur, zéro affectation `event_user`, aucune
 * référence dans le code hors sa propre création.
 *
 * ⚠️ **On ne retire que s'il n'est attribué à personne.** Si une base avait fini
 * par le donner à quelqu'un, le supprimer retirerait ses accès sans prévenir :
 * mieux vaut le laisser et le traiter à la main.
 */
return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'editor')->where('guard_name', 'web')->first();
        if ($role === null) {
            return;
        }

        if (DB::table('model_has_roles')->where('role_id', $role->id)->exists()) {
            return;
        }

        DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
        DB::table('roles')->where('id', $role->id)->delete();
    }

    public function down(): void
    {
        // Rien : recréer un rôle à tous les pouvoirs que personne ne voit n'aurait
        // aucun intérêt. Le seeder ne le recrée plus non plus.
    }
};
