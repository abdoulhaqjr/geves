<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refonte comptes & rôles - Phase 4 : la fonction d'édition « administrator »
 * (gérant d'un colloque) est renommée « gerant » (distincte du niveau global
 * « super_admin »). Les rôles cumulables par colloque puisent désormais dans un
 * catalogue à deux natures (scientifique / organisation) - voir StaffRoles.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('edition_user')->where('role', 'administrator')->update(['role' => 'gerant']);
    }

    public function down(): void
    {
        DB::table('edition_user')->where('role', 'gerant')->update(['role' => 'administrator']);
    }
};
