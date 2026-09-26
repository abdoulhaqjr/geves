<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formateurs enrichis : titre/grade (M., Mme, Pr., Dr.…), spécialité/fonction, et
 * photo de profil téléversée (sinon on retombe sur la photo du compte lié, sinon
 * un avatar par défaut).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formation_trainers', function (Blueprint $table) {
            $table->string('title', 40)->nullable()->after('formation_id');
            $table->string('specialty', 200)->nullable()->after('name');
            $table->string('photo_path', 500)->nullable()->after('specialty');
        });
    }

    public function down(): void
    {
        Schema::table('formation_trainers', function (Blueprint $table) {
            $table->dropColumn(['title', 'specialty', 'photo_path']);
        });
    }
};
