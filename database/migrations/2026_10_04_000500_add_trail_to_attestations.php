<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Synthèse du journal (`attestation_events`) portée par l'attestation.
 *
 * ⚠️ **Dénormalisation assumée, et pour une raison précise** : « qui n'a jamais
 * ouvert la sienne ? » est à la liste des attestations ce que « ce qui reste à
 * remettre » est au guichet - une question qu'on pose en filtrant une liste
 * paginée et triable. La reconstruire à chaque ligne depuis le journal
 * imposerait une sous-requête par colonne et par ligne, et interdirait le tri.
 * Le journal reste la source de vérité et se recalcule
 * (`AttestationTrail::recompute()`).
 *
 * ⚠️ `acknowledged_at` est l'ACCUSÉ DE RÉCEPTION, et il n'est posé que par le
 * TITULAIRE connecté à son espace. Une ouverture anonyme n'en est pas un : le
 * lien d'une attestation circule - recruteur, collègue, jury - et compter ces
 * visites comme une réception ferait croire que la personne a son document quand
 * c'est un tiers qui l'a lu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('delivered_by');
            $table->timestamp('first_viewed_at')->nullable()->after('acknowledged_at');
            $table->timestamp('last_viewed_at')->nullable()->after('first_viewed_at');
            $table->unsignedInteger('views_count')->default(0)->after('last_viewed_at');
            $table->timestamp('shared_at')->nullable()->after('views_count');
            $table->unsignedInteger('shares_count')->default(0)->after('shared_at');
            $table->timestamp('linkedin_at')->nullable()->after('shares_count');
            // « Jamais ouverte » est LA requête de relance.
            $table->index(['edition_id', 'first_viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropIndex(['edition_id', 'first_viewed_at']);
            $table->dropColumn([
                'acknowledged_at', 'first_viewed_at', 'last_viewed_at',
                'views_count', 'shared_at', 'shares_count', 'linkedin_at',
            ]);
        });
    }
};
