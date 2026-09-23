<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte comptes & rôles - Phase 1 (identité unifiée).
 *
 * Porte sur `users` les colonnes de profil qui n'existaient que sur `accounts`,
 * afin que `users` devienne l'identité unique (fusion à venir dans la migration
 * de données). Ajoute aussi `user_id` aux tables qui ne référençaient que le
 * compte participant. Rien n'est supprimé ici : `accounts` et le guard `account`
 * restent fonctionnels jusqu'à la Phase 2 (bascule de la connexion).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identité / profil (repris d'`accounts`).
            $table->string('civility', 20)->nullable()->after('name');
            $table->string('first_name', 150)->nullable()->after('civility');
            $table->string('last_name', 150)->nullable()->after('first_name');
            $table->string('grade', 100)->nullable()->after('last_name');
            $table->string('phone', 50)->nullable()->after('grade');
            $table->string('affiliation', 255)->nullable()->after('phone');
            $table->string('orcid', 19)->nullable()->after('affiliation');
            $table->timestamp('orcid_verified_at')->nullable()->after('orcid');
            $table->string('country', 100)->nullable()->after('orcid_verified_at');
            $table->string('city', 120)->nullable()->after('country');
            $table->text('bio')->nullable()->after('city');
            $table->string('photo_path', 500)->nullable()->after('bio');
            $table->json('meta')->nullable()->after('photo_path');
            $table->string('locale', 5)->nullable()->after('meta');

            // Double authentification (repris d'`accounts`).
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->string('two_factor_method', 10)->nullable()->after('two_factor_confirmed_at');
            $table->string('two_factor_otp')->nullable()->after('two_factor_method');
            $table->timestamp('two_factor_otp_expires_at')->nullable()->after('two_factor_otp');

            // Provenance / bascule : compte participant fusionné + reset forcé.
            $table->unsignedBigInteger('merged_account_id')->nullable()->after('two_factor_otp_expires_at');
            $table->boolean('must_reset_password')->default(false)->after('merged_account_id');
        });

        // `user_id` sur les tables qui ne pointaient que vers le compte participant.
        foreach (['participants', 'session_questions', 'session_polls'] as $t) {
            if (Schema::hasTable($t) && ! Schema::hasColumn($t, 'user_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->foreignId('user_id')->nullable()->after('id')->index();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['participants', 'session_questions', 'session_polls'] as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'user_id')) {
                Schema::table($t, function (Blueprint $table) use ($t) {
                    // SQLite refuse de supprimer une colonne indexée : on retire l'index d'abord.
                    $table->dropIndex($t.'_user_id_index');
                    $table->dropColumn('user_id');
                });
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'civility', 'first_name', 'last_name', 'grade', 'phone', 'affiliation',
                'orcid', 'orcid_verified_at', 'country', 'city', 'bio', 'photo_path', 'meta', 'locale',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'two_factor_method', 'two_factor_otp', 'two_factor_otp_expires_at',
                'merged_account_id', 'must_reset_password',
            ]);
        });
    }
};
