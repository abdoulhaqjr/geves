<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notes par critère de la grille d'évaluation structurée (JSON { key => 0..5 }).
 * La note globale /20 (colonne `score`) en est dérivée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('scores')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('scores');
        });
    }
};
