<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestion de projet par événement : chaque événement est pilotée comme un projet
 * (tâches façon kanban + jalons du cycle de vie du colloque).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo');      // todo | doing | review | done
            $table->string('priority', 20)->default('normal');  // low | normal | high | urgent
            $table->string('phase', 60)->nullable();            // regroupement par phase du colloque
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->unsignedInteger('position')->default(0);     // ordre dans la colonne kanban
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('pending');    // pending | done
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_milestones');
    }
};
