<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sondages live d'une séance : question + options, lancés par le modérateur, le
 * public vote (un choix par compte), résultats agrégés en direct.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('question', 300);
            $table->json('options'); // list<string>
            $table->string('status', 20)->default('open'); // open | closed
            $table->timestamps();

            $table->index(['session_id', 'status']);
        });

        Schema::create('session_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('session_polls')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('choice'); // index dans options
            $table->timestamps();

            $table->unique(['poll_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_poll_votes');
        Schema::dropIfExists('session_polls');
    }
};
