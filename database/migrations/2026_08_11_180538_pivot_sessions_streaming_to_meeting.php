<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot : la diffusion en direct passe d'un modèle « broadcast » (embed
 * YouTube/Vimeo) à une **visio interactive** (salle de réunion par session, où
 * les communicants présentent en ligne). Les anciens champs `stream_*`
 * (broadcast, jamais peuplés) sont remplacés par des champs `meeting_*`.
 *
 * - meeting_provider   : jitsi | custom (défaut config).
 * - meeting_room       : identifiant de salle (Jitsi) ; auto-généré si vide.
 * - meeting_url        : lien complet fourni (fournisseur « custom » / lien collé).
 * - meeting_status     : none | scheduled | live | ended (piloté par le staff).
 * - meeting_visibility : public | participants (qui peut rejoindre).
 * - recording_url      : conservé (replay après la session).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dropColumn(['stream_url', 'stream_status', 'stream_visibility']);
        });

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->string('meeting_provider', 30)->nullable()->after('room');
            $table->string('meeting_room', 120)->nullable()->after('meeting_provider');
            $table->string('meeting_url', 500)->nullable()->after('meeting_room');
            $table->string('meeting_status', 20)->default('none')->after('meeting_url');
            $table->string('meeting_visibility', 20)->default('participants')->after('meeting_status');
        });
    }

    public function down(): void
    {
        Schema::table('event_sessions', function (Blueprint $table) {
            $table->dropColumn(['meeting_provider', 'meeting_room', 'meeting_url', 'meeting_status', 'meeting_visibility']);
        });

        Schema::table('event_sessions', function (Blueprint $table) {
            $table->string('stream_url', 500)->nullable()->after('room');
            $table->string('stream_status', 20)->default('none')->after('stream_url');
            $table->string('stream_visibility', 20)->default('participants')->after('stream_status');
        });
    }
};
