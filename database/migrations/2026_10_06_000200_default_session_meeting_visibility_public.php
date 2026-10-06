<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accès à la visio d'une SÉANCE : public PAR DÉFAUT.
 *
 * Règle métier : tant que le gestionnaire n'a pas restreint l'accès aux seuls
 * participants inscrits, la visio d'une séance est ouverte à tous. La colonne
 * naissait à « participants », donc toute séance créée sans choix explicite
 * (formulaire, génération depuis le programme synthétique) retombait sur l'accès
 * restreint. On bascule le défaut de la colonne à « public ».
 *
 * ⚠️ On ne touche PAS aux lignes existantes (un gestionnaire a pu choisir
 * « participants » en connaissance de cause, et le seed exerce ce cas), ni à la
 * formation, qui reste réservée aux inscrits par nature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->string('meeting_visibility', 20)->default('public')->change();
        });
    }

    public function down(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->string('meeting_visibility', 20)->default('participants')->change();
        });
    }
};
