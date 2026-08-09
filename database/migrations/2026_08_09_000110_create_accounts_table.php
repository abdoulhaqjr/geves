<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes participants : authentification propre à la plateforme, distincte des
 * utilisateurs du back-office (table `users`). Un compte global par personne,
 * valable pour toutes les éditions. Adopte les conventions Laravel
 * (password, email_verified_at, remember_token) plutôt que les hachages WP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('civility', 20)->nullable();
            $table->string('first_name', 150)->default('');
            $table->string('last_name', 150)->default('');
            $table->string('grade', 100)->nullable();
            $table->string('email', 190)->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('phone', 50)->nullable();
            $table->string('affiliation', 255)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        // Broker de réinitialisation de mot de passe dédié au guard « account ».
        Schema::create('account_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_password_reset_tokens');
        Schema::dropIfExists('accounts');
    }
};
