<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membres des comités et panélistes (organisation, scientifique, supervision).
 * Le rapprochement de rôle avec un compte se fait par e-mail (repli sur le nom).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->string('person_name', 200)->default('');
            $table->string('person_title', 200)->nullable();
            $table->string('institution', 255)->nullable();
            $table->string('email', 200)->nullable()->index();
            $table->string('role', 40)->default('organisation');
            $table->string('photo_url', 500)->nullable();
            $table->text('bio')->nullable();
            $table->string('link', 500)->nullable();
            $table->smallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
