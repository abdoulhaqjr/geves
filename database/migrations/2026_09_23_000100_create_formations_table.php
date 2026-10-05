<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formations d'un événement : module activable, plusieurs formations par événement
 * (titre/thème, objectif, lieu/salle, frais — 0 = gratuite, dates), avec visio
 * optionnelle (couche App\Meetings, comme les sessions du programme).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('theme', 255)->nullable();
            $table->text('objective')->nullable();
            $table->string('location', 200)->nullable();
            $table->string('room', 120)->nullable();
            $table->decimal('fee', 12, 2)->default(0); // 0 = gratuite
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('published')->default(false); // formation active/visible
            $table->boolean('public')->default(false);     // affichée sur la page publique
            // Visio (réutilise App\Meetings) — remplie à la phase visio.
            $table->string('meeting_provider', 20)->nullable();
            $table->string('meeting_room', 120)->nullable();
            $table->string('meeting_url', 500)->nullable();
            $table->string('meeting_status', 20)->default('none');
            $table->string('meeting_visibility', 20)->default('participants');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};
