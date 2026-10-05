<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participants : présence d'une personne sur un événement donné (auteur,
 * congressiste/auditeur, relecteur, membre de comité…). Reliée au compte global
 * (`account_id`) quand la personne s'est connectée. Le rôle « fort » n'est pas
 * rétrogradé lors des upserts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->index();
            $table->string('first_name', 150)->default('');
            $table->string('last_name', 150)->default('');
            $table->string('email', 200)->default('')->index();
            $table->string('phone', 50)->nullable();
            $table->string('institution', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('title', 100)->nullable();
            $table->string('role', 30)->default('author')->index();
            $table->string('access_token', 64)->default('')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
