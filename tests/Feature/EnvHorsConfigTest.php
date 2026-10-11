<?php

use Illuminate\Support\Str;

/**
 * **`env()` ne s'appelle que depuis `config/`.**
 *
 * ⚠️ Dès que `php artisan config:cache` a tourné - ce qui est la règle en
 * production - Laravel ne charge plus le fichier `.env`, et le helper `env()`
 * rend `null` partout ailleurs que dans les fichiers de configuration, qui sont
 * lus au moment de FABRIQUER le cache.
 *
 * Ce n'est pas une règle de style. Un seeder lisait `env('ADMIN_PASSWORD')` :
 * en production, il refusait un mot de passe pourtant bien écrit dans le
 * `.env`, et bloquait un déploiement en cours. Le symptôme accuse le fichier
 * `.env`, la cause est dans le code.
 */
it('n’appelle jamais env() en dehors des fichiers de configuration', function () {
    $dossiers = ['app', 'database/seeders', 'routes', 'bootstrap'];

    $fautifs = [];

    foreach ($dossiers as $dossier) {
        $chemin = base_path($dossier);
        if (! is_dir($chemin)) {
            continue;
        }

        /** @var iterable<SplFileInfo> $fichiers */
        $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chemin));

        foreach ($fichiers as $fichier) {
            if ($fichier->getExtension() !== 'php') {
                continue;
            }

            $lignes = file((string) $fichier->getRealPath(), FILE_IGNORE_NEW_LINES) ?: [];

            foreach ($lignes as $i => $ligne) {
                // ⚠️ Ce qu'on exclut, et pourquoi : `->env(` est une méthode de
                // Process, `getenv(` est la fonction PHP, et `.env (` apparaît
                // dans des PHRASES qui citent le nom du fichier - un premier
                // jet attrapait ce dernier. D'où le point dans l'exclusion, et
                // l'absence d'espace toléré avant la parenthèse.
                if (! preg_match('/(?<![\w>.])env\(/', $ligne)) {
                    continue;
                }
                $nu = ltrim($ligne);
                if (str_starts_with($nu, '//') || str_starts_with($nu, '*') || str_starts_with($nu, '/*')) {
                    continue;
                }

                $relatif = Str::after(str_replace('\\', '/', (string) $fichier->getRealPath()), str_replace('\\', '/', base_path()).'/');
                $fautifs[] = $relatif.':'.($i + 1).'  '.trim($ligne);
            }
        }
    }
    sort($fautifs);

    expect($fautifs)->toBe([]);
});
