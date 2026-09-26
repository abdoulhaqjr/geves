<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signature du formateur (image détourée) : apposée sur les attestations de
 * formation et d'animation via l'élément « signatures » du concepteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formation_trainers', function (Blueprint $table) {
            $table->string('signature_path', 500)->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('formation_trainers', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
