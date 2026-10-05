<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attestations émises : participation, communication, modération, rapporteur.
 * Chaque attestation a un UUID de vérification (page publique + QR), et peut
 * être révoquée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->unique();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('type', 30)->default('');
            $table->foreignId('participant_id')->nullable()->index();
            $table->string('recipient_name', 200)->nullable();
            $table->string('recipient_email', 200)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
