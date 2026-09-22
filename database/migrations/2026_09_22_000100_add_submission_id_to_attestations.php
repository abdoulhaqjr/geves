<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attestations de communication = par communication ET par personne : on ajoute
 * la référence à la communication (nullable, car les autres types — participation,
 * modération… — restent liés au seul participant/édition).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->foreignId('submission_id')->nullable()->after('participant_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropIndex(['submission_id']);
            $table->dropColumn('submission_id');
        });
    }
};
