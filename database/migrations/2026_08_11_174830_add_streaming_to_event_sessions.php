<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Diffusion en direct (live streaming) des sessions du colloque. Fondations de
 * données uniquement - l'UI (formulaire back-office, page « regarder ») viendra
 * avec le mockup. Champs additifs et nullables : aucune session existante n'est
 * affectée.
 *
 * - stream_url        : lien de la diffusion (YouTube/Vimeo/embed personnalisé).
 * - stream_status     : none | scheduled | live | ended (piloté par le staff).
 * - stream_visibility : public | participants (qui peut regarder).
 * - recording_url     : lien du replay une fois la session terminée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->string('stream_url', 500)->nullable()->after('room');
            $table->string('stream_status', 20)->default('none')->after('stream_url');
            $table->string('stream_visibility', 20)->default('participants')->after('stream_status');
            $table->string('recording_url', 500)->nullable()->after('stream_visibility');
        });
    }

    public function down(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dropColumn(['stream_url', 'stream_status', 'stream_visibility', 'recording_url']);
        });
    }
};
