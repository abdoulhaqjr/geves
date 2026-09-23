<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscriptions à une formation (compte unifié `users`). Une inscription par
 * personne et par formation. `status` : registered (inscrit, gratuite ou frais
 * non réglés) → paid (frais réglés) ; le paiement passe par la couche Payment
 * avec `meta.flow = 'formation'` (distinct de la participation / des co-auteurs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formation_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('user_id')->index(); // compte unifié (users)
            $table->foreignId('participant_id')->nullable()->index();
            $table->string('status', 20)->default('registered')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['formation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_enrollments');
    }
};
