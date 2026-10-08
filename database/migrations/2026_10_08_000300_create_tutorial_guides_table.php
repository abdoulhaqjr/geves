<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorial_guides', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('intro');
            $table->json('steps');
            $table->string('audience')->default('admin');
            $table->json('participant_roles')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('video_path')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('video_visible_to_participants')->default(true);
            $table->string('video_generation_status')->default('none');
            $table->text('video_generation_prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorial_guides');
    }
};
