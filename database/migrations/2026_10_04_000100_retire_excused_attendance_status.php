<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Retrait du verdict « Excusé » : le pointage ne connaît plus que **présent** ou
 * **absent**.
 *
 * ⚠️ Les lignes déjà posées deviennent `absent`, et **pas** « non pointé ». Un
 * modérateur avait regardé sa salle et constaté que la personne n'y était pas :
 * c'est un verdict, pas un blanc. Le remettre à zéro effacerait son travail et
 * ferait remonter le compteur des gens « à appeler ». En aval, rien ne bouge -
 * seul `present` compte dans le PV (`SessionReportController`) et dans
 * l'attestation (`DocumentController`), donc « excusé » et « absent » y pesaient
 * déjà exactement le même poids.
 *
 * ⚠️ **Irréversible dans l'autre sens** : une fois versés dans `absent`, on ne
 * sait plus lesquels étaient excusés. `down()` ne fait donc rien plutôt que de
 * mentir en rendant un motif qu'il n'a pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('session_attendances')->where('status', 'excused')->update(['status' => 'absent']);
    }

    public function down(): void
    {
        // Rien : le motif n'est plus distinguable, et inventer la liste serait pire.
    }
};
