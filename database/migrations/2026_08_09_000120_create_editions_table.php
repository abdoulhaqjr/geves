<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Éditions : une occurrence datée d'un colloque, avec son cycle de vie
 * (brouillon → ouverte → clôturée → archivée), sa page publique, ses réglages
 * (frais, thématiques, contenu enrichi) stockés en JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colloque_id')->constrained('colloques')->cascadeOnDelete();
            $table->string('title', 255)->default('');
            $table->string('edition_label', 100)->default('');
            $table->text('theme')->nullable();
            $table->string('subtitle', 255)->nullable();
            $table->smallInteger('year')->default(0)->index();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('slug', 220)->nullable()->index();
            $table->boolean('submission_open')->default(false);
            $table->dateTime('submission_deadline')->nullable();
            $table->string('banner_url', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('booklet_url', 500)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editions');
    }
};
