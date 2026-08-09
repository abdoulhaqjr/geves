<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paiements : frais d'inscription réglés par participant (mobile money, carte
 * via passerelle, ou encaissement manuel). Modèle par participant, jamais par
 * communication (garde-fou anti-double-paiement au niveau applicatif).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('editions')->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->index();
            $table->unsignedBigInteger('submission_id')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('XOF');
            $table->string('method', 30)->default('manual');
            $table->string('gateway', 40)->default('manual');
            $table->string('gateway_ref', 191)->nullable()->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('receipt_no', 60)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('recorded_by')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
