<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refonte comptes & rôles — Phase 2b : Q&A / sondages de session.
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

            Schema::table($table, function (Blueprint $t) use ($table, $group) {
                $t->dropUnique($table.'_'.$group.'_account_id_unique');
                $t->unique([$group, 'user_id']);
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
