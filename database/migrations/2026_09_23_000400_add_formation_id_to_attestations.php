<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attestations de formation (participation & animation) : référence à la
 * formation concernée (nullable — les autres types n'y sont pas liés), sur le
 * même modèle que `submission_id` pour les communications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->foreignId('formation_id')->nullable()->after('submission_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropIndex(['formation_id']);
            $table->dropColumn('formation_id');
        });
    }
};
