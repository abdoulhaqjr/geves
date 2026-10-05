<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liaison auteurs ↔ utilisateurs : une ligne par auteur (correspondant ou
 * co-auteur) d'une communication. Permet d'associer un résumé à des comptes
 * existants (il apparaît alors dans « Mes communications » de plusieurs
 * personnes) et de suivre les invitations envoyées aux personnes non inscrites
 * (pour les auto-associer à la création de leur compte).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('role', 20)->default('coauthor');          // corresponding | coauthor
            $table->unsignedTinyInteger('author_index')->default(0);   // position dans authors JSON
            $table->string('name', 200)->default('');
            $table->string('email', 200)->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('affiliation', 255)->nullable();
            $table->string('match_type', 20)->nullable();              // self | email | phone | name | null
            $table->string('status', 20)->default('unmatched')->index(); // linked | invited | unmatched | declined
            $table->string('token', 64)->default('')->index();         // lien de déclinaison / invitation
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->index(['submission_id', 'author_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_authors');
    }
};
