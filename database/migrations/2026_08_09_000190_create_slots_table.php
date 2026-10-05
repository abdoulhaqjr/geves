<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Créneaux de passage : moments de présentation (salle, horaire) auxquels on
 * rattache une communication acceptée. Le participant peut choisir son créneau
 * parmi les disponibles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->index();
            $table->string('session_label', 255)->nullable();
            $table->string('room', 150)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->smallInteger('capacity')->default(1);
            $table->foreignId('submission_id')->nullable()->index();
            $table->foreignId('participant_id')->nullable();
            $table->boolean('chosen_by_participant')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
