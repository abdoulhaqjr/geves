<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Présence effective par séance : pointage d'un participant à l'entrée d'une
 * session (scan du badge en « mode séance »). Sert de preuve pour conditionner
 * l'attestation de participation à une présence réelle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->unique(['session_id', 'participant_id']);
            $table->index(['edition_id', 'participant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
