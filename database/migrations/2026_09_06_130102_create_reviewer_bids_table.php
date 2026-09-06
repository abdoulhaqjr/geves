<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidatures (« bids ») des relecteurs : préférence exprimée sur une
 * soumission (intéressé / neutre / indisponible) + auto-déclaration de conflit
 * d'intérêt, depuis le portail relecteur. Alimente l'attribution automatique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviewer_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('participants')->cascadeOnDelete();
            $table->string('interest')->default('neutral'); // interested | neutral | unavailable
            $table->boolean('conflict')->default(false);
            $table->timestamps();

            $table->unique(['submission_id', 'reviewer_id']);
            $table->index('reviewer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviewer_bids');
    }
};
