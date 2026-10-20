<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Collaborateurs d'une tâche de projet : les personnes ASSOCIÉES à la tâche,
 * en plus de son responsable.
 *
 * ⚠️ Le `assignee_id` de `project_tasks` N'EST PAS remplacé : une tâche garde
 * UN responsable - la personne qui en répond - et peut réunir PLUSIEURS
 * collaborateurs autour d'elle. Confondre les deux ferait disparaître la
 * notion de redevabilité, qui est précisément ce qu'un tableau de projet sert
 * à tenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Une personne n'est associée qu'une fois à la même tâche.
            $table->unique(['task_id', 'user_id'], 'ptc_task_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_collaborators');
    }
};
