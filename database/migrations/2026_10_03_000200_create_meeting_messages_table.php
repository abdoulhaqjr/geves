<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fil de discussion d'une salle de visio.
 *
 * ⚠️ Le chat vivait uniquement sur le canal de données de LiveKit : rien n'était
 * conservé. Qui arrivait en cours de séance - ou rechargeait sa page - trouvait
 * une salle muette et ne pouvait plus suivre ce qui s'y disait. Le fil est donc
 * archivé, et rejoué à l'arrivée.
 *
 * Morph : la salle sert aussi bien une séance du programme qu'une formation
 * (tout objet `Meetable`), et le fil appartient à l'objet, pas à une connexion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('meetable');
            // Identifiant posé par la salle : c'est lui que porte une citation ou
            // une réaction, avant même que le serveur ait répondu.
            $table->string('uid', 32)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->text('body');
            // ⚠️ La citation est RECOPIÉE (auteur + extrait) et non résolue par
            // jointure : le message cité peut être hors de la fenêtre chargée, et
            // une réponse sans son contexte ne veut plus rien dire.
            $table->string('reply_uid', 32)->nullable();
            $table->string('reply_author')->nullable();
            $table->string('reply_excerpt', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('meeting_message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_message_id')->constrained()->cascadeOnDelete();
            // Qui réagit, sous une forme stable d'une connexion à l'autre : le
            // compte quand il y en a un, le membre du staff sinon.
            $table->string('reactor', 40);
            $table->string('emoji', 16);
            $table->timestamps();
            // ⚠️ L'unicité est tenue par la BASE : deux clics rapides, ou deux
            // onglets, compteraient sinon deux fois la même personne.
            $table->unique(['meeting_message_id', 'reactor', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_message_reactions');
        Schema::dropIfExists('meeting_messages');
    }
};
