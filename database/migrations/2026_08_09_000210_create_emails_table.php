<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * File / journal des e-mails : envois immédiats journalisés et campagnes
 * programmées (rappels à une audience, traités par le planificateur).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->nullable()->index();
            $table->foreignId('participant_id')->nullable();
            $table->string('template', 60)->nullable();
            $table->string('recipient', 200)->default('');
            $table->text('subject')->nullable();
            $table->longText('body')->nullable();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
