<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Médias d'une événement : galerie photo, vidéos, livret, documents. Rendus sur la
 * page publique (surtout pour les événements archivées).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('type', 30)->default('photo');
            $table->string('url', 500)->default('');
            $table->string('title', 255)->nullable();
            $table->smallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
