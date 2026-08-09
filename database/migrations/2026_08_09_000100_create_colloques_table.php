<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Série de colloques : l'événement scientifique récurrent (ex. « Colloque
 * international de l'Université de Kara »). Chaque occurrence datée est une
 * « édition ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colloques', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->default('');
            $table->string('slug', 200)->default('')->index();
            $table->longText('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colloques');
    }
};
