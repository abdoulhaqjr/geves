<?php

use Illuminate\Support\Str;

/**
 * **Le tableau de bord participant existe aussi en anglais.**
 *
 * ⚠️ La refonte a remplacé une page dont les chaînes étaient traduites par une
 * page qui en portait cent de neuves, aucune traduite. En français rien ne se
 * voyait ; en anglais l'écran devenait un panachage - « Dashboard » et « My
 * documents » à côté de « CE QU'IL VOUS RESTE À RETIRER » et « Prêts à
 * retirer ». Une page à moitié traduite se lit plus mal qu'une page qui ne
 * l'est pas du tout.
 *
 * Ce cas lit les sources : c'est là que la chaîne naît, et c'est le seul
 * endroit où l'oubli se voit sans parler anglais.
 */
it('ne laisse aucune chaîne du tableau de bord sans traduction anglaise', function () {
    $sources = [
        'resources/views/account/dashboard.blade.php',
        'resources/views/account/_tdb-agenda.blade.php',
        'resources/views/account/_tdb-documents.blade.php',
        'app/Support/ParticipantDashboard.php',
    ];

    $cles = [];
    foreach ($sources as $relatif) {
        $src = (string) file_get_contents(base_path($relatif));
        preg_match_all('/(?:__|trans_choice)\(\s*\x27((?:[^\x27\x5c]|\x5c.)*)\x27/u', $src, $m);
        foreach ($m[1] as $brute) {
            $cles[str_replace(['\\\'', '\\\\'], ['\'', '\\'], $brute)] = $relatif;
        }
    }

    // La refonte en a posé plus de cent : si ce nombre s'effondre, c'est
    // l'extraction qui est cassée, pas la traduction qui est devenue inutile.
    expect(count($cles))->toBeGreaterThan(100);

    $en = json_decode((string) file_get_contents(base_path('lang/en.json')), true);

    $manquantes = [];
    foreach ($cles as $cle => $fichier) {
        if (! array_key_exists($cle, $en)) {
            $manquantes[] = Str::limit($cle, 60).'  ('.basename($fichier).')';
        }
    }
    sort($manquantes);

    expect($manquantes)->toBe([]);
});
