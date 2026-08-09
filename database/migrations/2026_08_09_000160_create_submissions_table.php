<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soumissions de résumés : titre, auteurs (JSON co-auteurs), résumé, axe,
 * type (oral/poster), statut du workflow, versionnage (resoumission via
 * `revision_of`). Accès auteur par jeton, référence courte `ref_code`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->index();
            $table->string('ref_code', 40)->default('')->index();
            $table->string('access_token', 64)->default('')->index();
            $table->text('title');
            $table->json('authors')->nullable();
            $table->longText('abstract')->nullable();
            $table->string('keywords', 500)->nullable();
            $table->string('track', 150)->nullable();
            $table->string('type', 30)->default('oral');
            $table->string('status', 30)->default('submitted')->index();
            $table->string('file_url', 500)->nullable();
            $table->unsignedBigInteger('revision_of')->default(0);
            $table->smallInteger('version')->default(1);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
