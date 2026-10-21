<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ Rejouable : trois colonnes sur deux tables, donc une reprise
        // possible à mi-chemin.
        // ⚠️ `file_path` n'est plus ALIMENTÉ depuis le passage à DocSpace
        // (21/10/2026) : la colonne reste pour les installations qui ont pu
        // porter un fichier local entre les deux.
        Schema::table('project_task_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('project_task_documents', 'format')) {
                $table->string('format', 8)->nullable()->after('title');
            }
            if (! Schema::hasColumn('project_task_documents', 'file_path')) {
                $table->string('file_path')->nullable()->after('format');
            }
        });

        Schema::table('project_task_document_versions', function (Blueprint $table) {
            if (! Schema::hasColumn('project_task_document_versions', 'file_path')) {
                $table->string('file_path')->nullable()->after('label');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_task_document_versions', fn (Blueprint $table) => $table->dropColumn('file_path'));
        Schema::table('project_task_documents', fn (Blueprint $table) => $table->dropColumn(['format', 'file_path']));
    }
};
