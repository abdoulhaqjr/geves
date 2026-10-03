<?php

use App\Models\MeetingMessageReaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une seule réaction par personne et par message.
 *
 * ⚠️ L'unicité portait aussi sur l'emoji : la même personne pouvait donc empiler
 * 👍 ET ❤️ ET 👏 sur un même message, et peser à elle seule plus que trois
 * autres. Choisir un autre emoji doit REMPLACER le sien, pas s'ajouter.
 *
 * Les doublons déjà posés sont ramenés à la réaction la plus récente - sinon le
 * nouvel index refuserait de se créer.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->degonfle();

        Schema::table('meeting_message_reactions', function (Blueprint $table) {
            $table->dropUnique(['meeting_message_id', 'reactor', 'emoji']);
            $table->unique(['meeting_message_id', 'reactor']);
        });
    }

    public function down(): void
    {
        Schema::table('meeting_message_reactions', function (Blueprint $table) {
            $table->dropUnique(['meeting_message_id', 'reactor']);
            $table->unique(['meeting_message_id', 'reactor', 'emoji']);
        });
    }

    /** Ne garde que la dernière réaction de chaque personne sur chaque message. */
    private function degonfle(): void
    {
        MeetingMessageReaction::query()
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (MeetingMessageReaction $r) => $r->meeting_message_id.'|'.$r->reactor)
            ->each(function ($groupe) {
                $groupe->skip(1)->each(fn (MeetingMessageReaction $r) => $r->delete());
            });
    }
};
