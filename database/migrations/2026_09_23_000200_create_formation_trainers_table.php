<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formateurs d'une formation : identifiés par e-mail (liés au compte unifié
 * `users` s'il existe, pour l'attestation d'animation + le rôle modérateur
 * visio), avec repli en saisie libre (nom/e-mail/affiliation).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formation_trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained('formations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->index(); // compte unifié (users)
            $table->string('name', 200);
            $table->string('email', 200)->nullable();
            $table->string('affiliation', 200)->nullable();
            $table->boolean('is_moderator')->default(true); // modérateur de la visio
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index('formation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_trainers');
    }
};
