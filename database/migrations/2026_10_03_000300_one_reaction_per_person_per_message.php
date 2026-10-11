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
    /** ⚠️ Nom explicite : la forme déduite ferait 65 caractères, un de trop pour MySQL. */
    private const ANCIEN_INDEX = 'mm_reactions_message_reactor_emoji_unique';

    public function up(): void
    {
        $this->degonfle();

        // ⚠️ CRÉER AVANT DE SUPPRIMER. `meeting_message_id` porte une clé
        // étrangère, et l'ancienne unicité la soutenait (ses colonnes en
        // forment le préfixe). La retirer en premier échoue sous MySQL en
        // erreur 1553 ; SQLite l'acceptait. Le nouvel index commence par la
        // même colonne et prend donc le relais.
        if (! Schema::hasIndex('meeting_message_reactions', ['meeting_message_id', 'reactor'])) {
            Schema::table('meeting_message_reactions', function (Blueprint $table) {
                $table->unique(['meeting_message_id', 'reactor']);
            });
        }

        if (Schema::hasIndex('meeting_message_reactions', self::ANCIEN_INDEX)) {
            Schema::table('meeting_message_reactions', function (Blueprint $table) {
                $table->dropUnique(self::ANCIEN_INDEX);
            });
        }
    }

    public function down(): void
    {
        Schema::table('meeting_message_reactions', function (Blueprint $table) {
            $table->unique(['meeting_message_id', 'reactor', 'emoji'], self::ANCIEN_INDEX);
            $table->dropUnique(['meeting_message_id', 'reactor']);
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
