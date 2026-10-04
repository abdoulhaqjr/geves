<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passage d'une communication **constaté par le modérateur** pendant la séance.
 *
 * ⚠️ L'information existait déjà dans la salle de visio (« Marquer effectuée »),
 * mais seulement dans les navigateurs : elle partait sur le canal de données et
 * mourait avec la séance. Elle devient un fait tenu en base, parce qu'elle
 * déclenche l'émission des attestations des auteurs - et qu'on doit pouvoir
 * dire qui l'a constaté, et quand.
 *
 * ⚠️ `presented_by` est l'identifiant d'un **compte participant** (le modérateur
 * ou le rapporteur de la séance), pas d'un agent du back-office : c'est celui
 * qui tenait la salle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->timestamp('presented_at')->nullable()->after('chosen_by_participant');
            $table->foreignId('presented_by')->nullable()->after('presented_at');
        });
    }

    public function down(): void
    {
        Schema::table('slots', function (Blueprint $table) {
            $table->dropColumn(['presented_at', 'presented_by']);
        });
    }
};
