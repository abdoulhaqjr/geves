<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identifiant de conversation du chatbot : au fil d'une même discussion, les
 * tours suivants (souvent de simples précisions demandées par Gesco) MODIFIENT
 * la réclamation existante au lieu d'en créer une nouvelle. On relie donc chaque
 * réclamation à sa conversation d'origine.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('conversation')->nullable()->after('source');
            $table->index(['conversation', 'source']);
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropIndex(['conversation', 'source']);
            $table->dropColumn('conversation');
        });
    }
};
