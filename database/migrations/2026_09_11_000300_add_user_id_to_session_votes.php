<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte comptes & rôles - Phase 2b : Q&A / sondages de session.
 *
 * Les votes (questions & sondages) référençaient le compte participant
 * (`account_id`, NOT NULL + FK vers `accounts` + unicité par votant). L'identité
 * unifiée vote désormais via `user_id`. On ajoute `user_id`, on rebranche
 * l'unicité, et on relâche l'ancien `account_id` (rendu nullable).
 */
return new class extends Migration
{
    /** @var array<string, string> table => colonne de regroupement du vote */
    private array $tables = [
        'session_question_votes' => 'question_id',
        'session_poll_votes' => 'poll_id',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $group) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (! Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('user_id')->nullable()->after('id')->index();
                });
            }

            // Backfill depuis la correspondance account → user (portable SQLite/MySQL).
            foreach (DB::table('users')->whereNotNull('merged_account_id')->get(['id', 'merged_account_id']) as $u) {
                DB::table($table)->where('account_id', $u->merged_account_id)->update(['user_id' => $u->id]);
            }

            $ancien = $table.'_'.$group.'_account_id_unique';
            $nouveau = $table.'_'.$group.'_user_id_unique';

            // ⚠️ CRÉER LE NOUVEL INDEX AVANT DE SUPPRIMER L'ANCIEN. MySQL exige
            // que chaque clé étrangère soit soutenue par un index dont ses
            // colonnes forment le PRÉFIXE : l'unicité `(question_id,
            // account_id)` soutenait donc la clé étrangère sur `question_id`,
            // et la retirer en premier échouait en erreur 1553 (« Cannot drop
            // index: needed in a foreign key constraint »). SQLite, lui,
            // l'acceptait - le défaut ne pouvait apparaître qu'en production.
            // Le nouvel index commence par la même colonne : une fois posé, il
            // prend le relais et l'ancien devient libre.
            if (! Schema::hasIndex($table, $nouveau)) {
                Schema::table($table, function (Blueprint $t) use ($group) {
                    $t->unique([$group, 'user_id']);
                });
            }

            // ⚠️ Les gardes rendent la migration REJOUABLE : elle a échoué une
            // fois à mi-chemin sur un vrai déploiement, colonne déjà ajoutée.
            if (Schema::hasIndex($table, $ancien)) {
                Schema::table($table, function (Blueprint $t) use ($ancien) {
                    $t->dropUnique($ancien);
                });
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('account_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table => $group) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($table, $group) {
                $t->dropUnique($table.'_'.$group.'_user_id_unique');
                $t->dropIndex($table.'_user_id_index');
                $t->dropColumn('user_id');
                $t->unique([$group, 'account_id']);
            });
        }
    }
};
