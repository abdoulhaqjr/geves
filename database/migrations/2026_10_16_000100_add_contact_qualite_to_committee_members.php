<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Téléphone et « qualité » (fonction : Président, Vice-président, Rapporteur…)
 * des membres de comité. Alimentent le TABLEAU du comité d'organisation du
 * modèle de livret « Document général du colloque » (colonnes N° / Nom & Prénom /
 * Grade / Contacts / Qualité). L'e-mail existe déjà ; le grade est `person_title`.
 *
 * ⚠️ Rejouable (gardes `hasColumn`) : une migration peut échouer à mi-chemin et
 * la reprise ne doit pas rejouer un ajout déjà fait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('committee_members', function (Blueprint $table) {
            if (! Schema::hasColumn('committee_members', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('committee_members', 'qualite')) {
                $table->string('qualite', 120)->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('committee_members', function (Blueprint $table) {
            foreach (['phone', 'qualite'] as $col) {
                if (Schema::hasColumn('committee_members', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
