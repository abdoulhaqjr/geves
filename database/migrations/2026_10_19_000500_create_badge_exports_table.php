<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_exports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('badge_count')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->string('path')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'user_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_exports');
    }
};
