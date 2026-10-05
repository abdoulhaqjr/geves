<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Affectation d'un membre du staff (User) à un événement avec une fonction
 * (organisation, secrétariat, comptabilité, accueil…). Support de l'étanchéité
 * d'accès par événement : chaque colloque a sa propre équipe. Un même utilisateur
 * peut cumuler plusieurs fonctions sur un événement (une ligne par fonction).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['event_id', 'user_id', 'role']);
            $table->index(['user_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_user');
    }
};
