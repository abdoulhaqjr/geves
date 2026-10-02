<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Présence par séance, tenue À LA MAIN par le modérateur ou le rapporteur de la
 * séance.
 *
 * ⚠️ Une table du même nom a existé puis été supprimée (migration
 * 2026_09_17_000200) : elle n'était alimentée QUE par le scan de badge, retiré
 * depuis. Elle revient avec une autre source - une personne qui pointe pendant
 * la séance - et un `status` explicite, parce qu'un absent CONSTATÉ n'est pas la
 * même information qu'un participant qu'on n'a pas pointé.
 *
 * `marked_by` garde qui a pointé : la présence conditionne une attestation, elle
 * doit être imputable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('status', 10)->default('present');
            $table->foreignId('marked_by')->nullable()->index();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            // Un seul verdict par personne et par séance.
            $table->unique(['session_id', 'participant_id']);
            // Comptages « présents d'une séance » et « séances d'un participant ».
            $table->index(['session_id', 'status']);
            $table->index(['participant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
