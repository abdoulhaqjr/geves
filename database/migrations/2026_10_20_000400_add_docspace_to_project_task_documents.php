<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ Une migration doit pouvoir être REJOUÉE : celle-ci ajoute trois
        // colonnes sur deux tables, et un échec à mi-chemin laisse les premières
        // en place. Sans garde, la reprise rejoue un geste déjà fait et échoue
        // à son tour, en plein déploiement.
        Schema::table('project_tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('project_tasks', 'docspace_folder_id')) {
                $table->string('docspace_folder_id')->nullable();
            }
            if (! Schema::hasColumn('project_tasks', 'docspace_request_token')) {
                $table->text('docspace_request_token')->nullable();
            }
        });
        Schema::table('project_task_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('project_task_documents', 'docspace_file_id')) {
                $table->string('docspace_file_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_task_documents', fn (Blueprint $table) => $table->dropColumn('docspace_file_id'));
        Schema::table('project_tasks', fn (Blueprint $table) => $table->dropColumn(['docspace_folder_id', 'docspace_request_token']));
    }
};
