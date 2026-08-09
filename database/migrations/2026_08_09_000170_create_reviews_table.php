<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relectures / évaluations en aveugle : recommandation, note /20, commentaires
 * à l'auteur et commentaires confidentiels. Le relecteur est un participant
 * (rôle « reviewer »).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('submissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->index();
            $table->string('recommendation', 30)->default('');
            $table->decimal('score', 5, 2)->nullable();
            $table->longText('comments')->nullable();
            $table->longText('comments_private')->nullable();
            $table->string('status', 20)->default('assigned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
