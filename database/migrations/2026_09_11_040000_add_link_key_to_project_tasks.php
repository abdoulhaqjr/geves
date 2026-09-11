<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien contextuel d'une tâche vers la feature concernée du back-office
 * (Soumissions, Participants, Documents, Programme…), filtrée sur l'édition.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('link_key', 40)->nullable()->after('phase');
        });
    }

    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn('link_key');
        });
    }
};
