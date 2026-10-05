<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Série de colloques : l'événement scientifique récurrent (ex. « Colloque
 * international de l'Université de Kara »). Chaque occurrence datée est une
 * « événement ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_series', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->default('');
            $table->string('slug', 200)->default('')->index();
            // Type de la série d'événements (colloque, séminaire, journées scientifiques…).
            // Porté par la série : toutes ses occurrences (événements) en héritent.
            $table->string('type')->default('colloque')->index();
            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_series');
    }
};
