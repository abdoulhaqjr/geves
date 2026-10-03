<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D'où vient un pointage : `manual` (le modérateur / le rapporteur l'a posé) ou
 * `meeting` (la personne est entrée dans la visio de la séance).
 *
 * ⚠️ Sans cette colonne, les deux se confondraient. Ce n'est pas la même valeur
 * de preuve : un verdict humain vient de quelqu'un qui a regardé la salle, un
 * pointage automatique vient d'un clic sur « Rejoindre ». Le modérateur doit voir
 * ce qu'il n'a pas posé lui-même, et le code doit savoir ce qu'il a le droit
 * d'écraser (jamais un verdict humain).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_attendances', function (Blueprint $table) {
            $table->string('source', 10)->default('manual')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('session_attendances', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
