<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Téléphone du formateur : second identifiant fort (avec l'e-mail) pour le
 * rapprochement avec un compte existant, sur le modèle des co-auteurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formation_trainers', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('formation_trainers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
