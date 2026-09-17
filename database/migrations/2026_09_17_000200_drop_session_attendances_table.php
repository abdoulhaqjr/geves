<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Retrait de la fonctionnalité de scan de badge : la table des présences par
 * séance (alimentée uniquement par le pointage) n'a plus lieu d'être.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('session_attendances');
    }

    public function down(): void
    {
        // Fonctionnalité retirée : aucune reconstruction.
    }
};
