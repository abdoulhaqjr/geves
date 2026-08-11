<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préférences de notification par type et par canal (in-app / e-mail), pour les
 * comptes participants et le personnel back-office. JSON libre :
 * { "<kind>": { "inapp": bool, "email": bool }, … }. Une clé absente = défaut du
 * registre App\Notifications\NotificationKinds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('meta');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('accounts', fn (Blueprint $table) => $table->dropColumn('notification_preferences'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notification_preferences'));
    }
};
