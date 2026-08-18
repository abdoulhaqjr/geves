<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Double authentification par SMS (OTP), commutable avec le TOTP existant :
 * méthode active + code à usage unique (haché) et son expiration. Le numéro de
 * téléphone (`phone`) existe déjà sur la table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('accounts', 'two_factor_method')) {
                $table->string('two_factor_method', 10)->nullable()->after('two_factor_confirmed_at');
            }
            if (! Schema::hasColumn('accounts', 'two_factor_otp')) {
                $table->string('two_factor_otp')->nullable()->after('two_factor_method');
            }
            if (! Schema::hasColumn('accounts', 'two_factor_otp_expires_at')) {
                $table->timestamp('two_factor_otp_expires_at')->nullable()->after('two_factor_otp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_method', 'two_factor_otp', 'two_factor_otp_expires_at']);
        });
    }
};
