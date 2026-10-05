<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions de l'événement : soit d'atelier (rattachée à un atelier), soit
 * plénière (cérémonie d'ouverture, conférence plénière…). Modérateur et
 * rapporteur donnent lieu à des attestations dédiées.
 *
 * Nommée `event_sessions` car `sessions` est réservée par Laravel (driver de
 * session en base).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('atelier_id')->nullable()->index();
            $table->string('type', 20)->default('atelier'); // atelier | plenary
            $table->string('label', 255)->default('');
            $table->string('room', 150)->nullable();
            $table->date('session_day')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('moderator_name', 200)->nullable();
            $table->string('moderator_email', 200)->nullable();
            $table->string('rapporteur_name', 200)->nullable();
            $table->string('rapporteur_email', 200)->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
