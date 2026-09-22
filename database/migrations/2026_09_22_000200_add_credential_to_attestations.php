<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certification cryptographique : on stocke la preuve immuable de l'attestation.
 *
 *  - `credential` : le Verifiable Credential signé (JWS compact EdDSA / VC-JWT),
 *    source de vérité de la vérification hors-ligne ;
 *  - `key_id`     : identifiant (kid) de la clé de l'émetteur ayant signé ;
 *  - `signed_at`  : horodatage de la signature.
 *
 * Colonnes nullable : une attestation émise sans clé configurée reste valide
 * (fail-open), simplement non signée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->longText('credential')->nullable()->after('meta');
            $table->string('key_id', 191)->nullable()->after('credential');
            $table->dateTime('signed_at')->nullable()->after('key_id');
        });
    }

    public function down(): void
    {
        Schema::table('attestations', function (Blueprint $table) {
            $table->dropColumn(['credential', 'key_id', 'signed_at']);
        });
    }
};
