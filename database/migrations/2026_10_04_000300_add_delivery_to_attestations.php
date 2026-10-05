<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remise en main propre d'une attestation **papier**.
 *
 * ⚠️ Émettre n'est pas remettre. En délivrance physique, une attestation peut
 * être émise, imprimée… et attendre des mois dans une chemise parce que personne
 * ne sait ce qui reste à distribuer. Cette trace répond à « qui n'a pas encore
 * retiré la sienne ? » - la seule question que le secrétariat se pose au guichet.
 *
 * ⚠️ `delivered_by` est un agent du **back-office** (table `users`, guard web) :
 * la remise engage le secrétariat, pas le bénéficiaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('issued_at');
            $table->foreignId('delivered_by')->nullable()->after('delivered_at');
            // « Ce qui reste à remettre » est LA requête du guichet.
            $table->index(['event_id', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'delivered_at']);
            $table->dropColumn(['delivered_at', 'delivered_by']);
        });
    }
};
