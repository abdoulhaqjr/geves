<?php

use Illuminate\Support\Facades\DB;

/**
 * **Le schéma doit tenir sur MySQL, qu'on développe sur SQLite ou non.**
 *
 * ⚠️ Les tests tournent sur SQLite, la production sur MySQL. Deux limites de
 * MySQL n'existent pas sous SQLite, et ne se découvrent donc qu'en ligne, au
 * milieu d'un déploiement :
 *
 * 1. Un identifiant ne peut pas dépasser **64 caractères**. Le nom d'index
 *    déduit par Laravel pour une unicité à trois colonnes en faisait 65.
 * 2. Toute clé étrangère doit rester soutenue par un index dont ses colonnes
 *    forment le préfixe. Retirer l'index avant d'avoir posé son remplaçant
 *    échoue en erreur 1553 - c'est arrivé, sur le vrai serveur.
 *
 * Le premier point se vérifie ici. Le second se tient à l'écriture : dans une
 * migration, **créer le nouvel index avant de supprimer l'ancien**.
 */
it('ne donne à aucun index un nom que MySQL refuserait', function () {
    $indexes = DB::select(
        "SELECT name, tbl_name FROM sqlite_master
         WHERE type = 'index' AND name NOT LIKE 'sqlite_%'"
    );

    // Si la requête ne rend rien, c'est le test qui est cassé, pas le schéma.
    expect(count($indexes))->toBeGreaterThan(20);

    $trop = [];
    foreach ($indexes as $index) {
        $longueur = strlen((string) $index->name);
        if ($longueur > 64) {
            $trop[] = $index->name.' ('.$longueur.' caractères, table '.$index->tbl_name.')';
        }
    }
    sort($trop);

    expect($trop)->toBe([]);
})->skip(
    fn () => DB::connection()->getDriverName() !== 'sqlite',
    'Lecture du catalogue propre à SQLite.'
);
