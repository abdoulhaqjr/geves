<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Validation d'un rapport de séance par le secrétariat : une fois validé
 * (ou amélioré) par le secrétariat, le rapporteur ne peut plus le modifier.
 * Le statut `validated` complète draft/submitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_reports', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('submitted_at');
            $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn('validated_at');
        });
    }
};
