<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réclamations & incidents. Créés le plus souvent par l'assistante Gesco
 * (chatbot public) lorsqu'elle ne peut pas résoudre le souci d'un participant,
 * puis traités au back-office (admin / gestionnaires / personnes déléguées).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();       // contact (nom)
            $table->string('email')->nullable();      // contact (e-mail)
            $table->string('type')->default('reclamation'); // reclamation | incident
            $table->string('category')->nullable();   // paiement, soumission, badge, technique, autre…
            $table->string('subject');
            $table->longText('message');              // description du participant
            $table->string('status')->default('open'); // open | in_progress | resolved | closed
            $table->string('source')->default('chatbot'); // chatbot | manual
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('resolution')->nullable();
            $table->json('meta')->nullable();         // analyses IA, journal…
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
