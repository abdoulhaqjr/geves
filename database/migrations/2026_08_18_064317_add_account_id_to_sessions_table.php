<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rattache une session au compte participant qui l'a ouverte (guard « account »).
 * La colonne native `user_id` de Laravel est renseignée depuis le guard par
 * défaut (web) : ce marqueur dédié permet de lister les sessions actives d'un
 * compte sans collision avec les utilisateurs du back-office.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sessions') || Schema::hasColumn('sessions', 'account_id')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id')->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'account_id')) {
            Schema::table('sessions', function (Blueprint $table): void {
                $table->dropColumn('account_id');
            });
        }
    }
};
