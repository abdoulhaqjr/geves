<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modèles de documents (badges, attestations) : une mise en page JSON par
 * événement, type de document (badge|attestation) et sous-type (default pour les
 * badges ; participation/communication/moderation/rapporteur pour les attestations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('kind', 20);            // badge | attestation
            $table->string('type', 30)->default('default');
            $table->json('layout')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'kind', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
