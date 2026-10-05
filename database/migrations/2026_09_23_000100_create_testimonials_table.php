<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Témoignages recueillis auprès des personnes ayant participé aux événements.
 * Modérés au niveau plateforme, puis un sous-ensemble « à la une » est affiché
 * sur la page d'accueil (sélection proposée par l'IA selon l'impact, ajustable
 * par l'admin : mise en avant + ordre manuels).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            // Auteur (compte). Nullable : l'admin peut saisir un témoignage pour un tiers.
            $table->foreignId('user_id')->nullable()->index();
            // Contexte optionnel : à propos de quel colloque/événement.
            $table->foreignId('event_id')->nullable()->index();
            $table->string('author_name', 160);
            $table->string('author_role', 160)->nullable();
            $table->string('author_affiliation', 200)->nullable();
            $table->text('quote');
            $table->unsignedTinyInteger('rating')->nullable();
            // Cycle de vie de modération.
            $table->string('status', 20)->default('pending')->index();
            // Mise en avant sur l'accueil + ordre manuel.
            $table->boolean('featured')->default(false)->index();
            $table->unsignedInteger('position')->default(0);
            // Évaluation IA de l'impact (à la demande).
            $table->unsignedTinyInteger('impact_score')->nullable();
            $table->string('impact_reason', 300)->nullable();
            // Consentement de publication (obligatoire pour un dépôt participant).
            $table->boolean('consent')->default(false);
            $table->dateTime('moderated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
