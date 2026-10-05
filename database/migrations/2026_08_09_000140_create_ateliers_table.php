<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ateliers : regroupent un ou plusieurs axes thématiques de l'événement et
 * contiennent une ou plusieurs sessions. `tracks` = liste JSON des axes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ateliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('title', 255)->default('');
            $table->json('tracks')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ateliers');
    }
};
