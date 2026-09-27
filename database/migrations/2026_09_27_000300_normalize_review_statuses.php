<?php

use App\Models\Review;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rattrape les relectures dont le statut n'appartient pas au vocabulaire
 * (Review::STATUSES). Les jeux de démonstration créaient des relectures en
 * « pending » : avant le cycle de vie, tout ce qui n'était pas `completed`
 * était traité pareil, donc la valeur passait inaperçue ; désormais un statut
 * inconnu sort des compteurs (ni actif, ni en attente) et la relecture devient
 * invisible pour le comité.
 *
 * On en profite pour donner une échéance aux relectures encore dues qui n'en
 * ont pas : assignation + durée standard. Une relecture confiée il y a trois
 * mois et jamais rendue apparaîtra donc « en retard » - c'est la réalité.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reviews')
            ->whereNotIn('status', array_keys(Review::STATUSES))
            ->update(['status' => 'invited']);

        // Échéance manquante sur une relecture encore due (lignes créées avant
        // l'introduction de `due_at`). Calcul en PHP : portable quel que soit
        // le moteur (pas de fonction de date propre à SQLite/MySQL).
        DB::table('reviews')
            ->whereIn('status', ['invited', 'accepted'])
            ->whereNull('due_at')
            ->select('id', 'assigned_at', 'created_at')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $from = $row->assigned_at ?: $row->created_at;
                    if ($from === null) {
                        continue;
                    }
                    DB::table('reviews')->where('id', $row->id)->update([
                        'due_at' => Carbon\Carbon::parse($from)->addDays(Review::DEFAULT_DUE_DAYS),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Irréversible : on ne sait pas distinguer les lignes rattrapées des
        // invitations légitimes.
    }
};
