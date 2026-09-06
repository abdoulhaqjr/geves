<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Affectation d'un membre du staff (User) à une édition avec une fonction
 * (organisation, secrétariat, comptabilité, accueil…). Support de l'étanchéité
 * d'accès par édition : chaque colloque a sa propre équipe. Un même utilisateur
 * peut cumuler plusieurs fonctions sur une édition (une ligne par fonction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edition_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['edition_id', 'user_id', 'role']);
            $table->index(['user_id', 'edition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edition_user');
    }
};
