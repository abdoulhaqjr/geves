<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ Rejouable : la colonne porte le MÊME nom que celle posée sur
        // `project_tasks` par la migration précédente, et on relit donc bien la
        // bonne table avant d'écrire.
        Schema::table('project_task_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('project_task_documents', 'docspace_request_token')) {
                $table->text('docspace_request_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_task_documents', fn (Blueprint $table) => $table->dropColumn('docspace_request_token'));
    }
};
