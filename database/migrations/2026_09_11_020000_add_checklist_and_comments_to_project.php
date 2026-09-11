<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checklists (sous-tâches, stockées en JSON sur la tâche) et commentaires
 * (fil de discussion relationnel, avec auteur) sur les tâches de projet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('description');
        });

        Schema::create('project_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['task_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_comments');
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn('checklist');
        });
    }
};
