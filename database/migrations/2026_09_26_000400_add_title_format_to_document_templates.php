<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libellé et format d'un modèle : renseignés à la création, avant d'ouvrir le
 * concepteur. Le format ne concerne que les badges (A6 / A7 / CR80) ; la portée
 * reste déduite du type (elle pilote l'émission, elle ne se choisit pas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('title', 150)->nullable()->after('type');
            $table->string('format', 20)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn(['title', 'format']);
        });
    }
};
