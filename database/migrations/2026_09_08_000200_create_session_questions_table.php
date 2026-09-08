<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions du public posées en séance (Q&A live) : une par intervention, avec
 * votes du public et modération (répondue / masquée) par le modérateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('event_sessions')->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 150)->default('');
            $table->text('body');
            $table->string('status', 20)->default('pending'); // pending | answered | hidden
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'status']);
        });

        Schema::create('session_question_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('session_questions')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['question_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_question_votes');
        Schema::dropIfExists('session_questions');
    }
};
