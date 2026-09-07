<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date de vérification de l'ORCID via OAuth (« connexion ORCID ») : quand elle
 * est renseignée, l'identifiant a été confirmé par ORCID (appartenance prouvée).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('orcid_verified_at')->nullable()->after('orcid');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('orcid_verified_at');
        });
    }
};
