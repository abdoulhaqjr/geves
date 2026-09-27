<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cycle de vie d'une relecture. Jusqu'ici une relecture n'avait que deux états
 * (`assigned` → `completed`) : impossible de refuser une attribution, de savoir
 * pour quand elle est due, ni de distinguer « pas encore commencée » de
 * « en retard ». On ajoute l'échéance, la traçabilité de l'attribution et la
 * réponse du relecteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable()->after('status');
            $table->foreignId('assigned_by_id')->nullable()->after('assigned_at');
            // Justification produite par le moteur d'attribution automatique.
            $table->string('assign_reason', 200)->nullable()->after('assigned_by_id');
            $table->timestamp('due_at')->nullable()->after('assign_reason');
            $table->timestamp('responded_at')->nullable()->after('due_at');
            $table->string('decline_reason', 500)->nullable()->after('responded_at');

            $table->index(['status', 'due_at']);
        });

        // `assigned` devient `invited` : le relecteur n'a pas encore répondu.
        DB::table('reviews')->where('status', 'assigned')->update(['status' => 'invited']);

        // Les relectures existantes n'ont pas de date d'attribution : on prend
        // leur création, qui en est la meilleure approximation.
        DB::statement('update reviews set assigned_at = created_at where assigned_at is null');
    }

    public function down(): void
    {
        DB::table('reviews')->where('status', 'invited')->update(['status' => 'assigned']);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_at']);
            $table->dropColumn(['assigned_at', 'assigned_by_id', 'assign_reason', 'due_at', 'responded_at', 'decline_reason']);
        });
    }
};
