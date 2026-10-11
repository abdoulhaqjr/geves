<?php

use Illuminate\Pagination\LengthAwarePaginator;

/**
 * **Une clé `__()` d'un seul mot peut rendre un TABLEAU.**
 *
 * ⚠️ Laravel lit la partie d'une clé qui précède le premier point comme un
 * **groupe** de traduction. `__('Pagination')` cherche donc d'abord la clé dans
 * `lang/<locale>.json` ; faute de l'y trouver, il charge le GROUPE
 * `pagination`, et le framework en livre un - `auth`, `pagination`,
 * `passwords`, `validation`. Il rend alors le tableau entier du fichier, et
 * `htmlspecialchars()` lève « must be of type string, array given ».
 *
 * ⚠️ Ce n'est pas théorique, et c'est pire que ça en a l'air : le défaut
 * n'apparaît **que dans une langue** (celle qui a un fichier de groupe, donc
 * l'anglais) et **que sur un système de fichiers insensible à la casse**
 * (Windows, macOS), où `lang/en/Pagination.php` tombe sur `pagination.php`.
 * Sous Linux, la production ne voyait rien. `/admin/utilisateurs` rendait donc
 * une erreur 500 en anglais sur tout poste de développement, et fonctionnait
 * en ligne.
 *
 * La parade : une clé qui porte le nom d'un groupe DOIT exister dans
 * `lang/en.json`, puisque la recherche JSON passe en premier et court-circuite
 * le groupe. « Validation » (étape du dépôt) est dans ce cas et reste valide.
 */
it('ne laisse aucune clé d’un seul mot entrer en collision avec un groupe de traduction', function () {
    $groupes = collect((array) glob(base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/*.php')))
        ->map(fn ($c) => mb_strtolower(pathinfo((string) $c, PATHINFO_FILENAME)))
        ->all();

    expect($groupes)->toContain('pagination');

    $en = json_decode((string) file_get_contents(base_path('lang/en.json')), true);

    $fautives = [];

    foreach (['resources/views', 'app'] as $dossier) {
        /** @var iterable<SplFileInfo> $fichiers */
        $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dossier)));

        foreach ($fichiers as $fichier) {
            if (! in_array($fichier->getExtension(), ['php'], true)) {
                continue;
            }

            $src = (string) file_get_contents((string) $fichier->getRealPath());
            preg_match_all('/(?:__|trans)\(\s*\x27([^\x27\x5c.]+)\x27/u', $src, $m);

            foreach (array_unique($m[1]) as $cle) {
                if (! in_array(mb_strtolower($cle), $groupes, true)) {
                    continue;
                }
                // Présente dans le JSON : la recherche JSON l'emporte, pas de collision.
                if (array_key_exists($cle, $en)) {
                    continue;
                }

                $relatif = str_replace('\\', '/', (string) $fichier->getRealPath());
                $relatif = substr($relatif, strlen(str_replace('\\', '/', base_path())) + 1);
                $fautives[] = $relatif.'  __(\''.$cle.'\')';
            }
        }
    }
    sort($fautives);

    expect(array_values(array_unique($fautives)))->toBe([]);
});

/**
 * Le symptôme lui-même, du côté où il se voit : la page des utilisateurs est
 * paginée, et c'est sa barre de pagination qui portait la clé fautive.
 */
it('rend la pagination en anglais sans lever d’erreur', function () {
    app()->setLocale('en');

    expect(__('Navigation des pages'))->toBeString();

    // ⚠️ La vraie garde : le libellé accessible de la barre doit rester une
    // chaîne quelle que soit la langue. Sous `en` et sur un disque insensible
    // à la casse, l'ancienne clé rendait ici un tableau.
    $vue = view('vendor.pagination.ukc', [
        'paginator' => new LengthAwarePaginator(range(1, 20), 40, 20, 1),
        'elements' => [],
    ])->render();

    expect($vue)->toContain('ukc-pagination');
});
