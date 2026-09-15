<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rapport de séance rédigé par le rapporteur (une par séance). Alimente l'espace
 * rapporteur côté participant : synthèse des communications, points saillants et
 * recommandations, avec un état brouillon → soumis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('summary')->nullable();
            $table->text('highlights')->nullable();
            $table->unsignedInteger('attendance')->nullable();
            $table->string('status')->default('draft'); // draft | submitted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_reports');
    }
};
