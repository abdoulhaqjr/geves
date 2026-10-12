<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SoftDeletes sur les comptes : la suppression d'un utilisateur depuis le
 * back-office est désormais réversible (colonne deleted_at). Le fournisseur
 * d'authentification Eloquent applique le scope SoftDeleting -> un compte
 * supprimé ne peut plus se connecter ni être retrouvé par liaison de route.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
