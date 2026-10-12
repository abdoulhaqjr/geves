<?php

use App\Support\AiAgents;

/**
 * **L'assistante publique s'appelle « Peace » par défaut, et son nom se règle.**
 *
 * ⚠️ Le nom était recopié en dur dans six libellés (bouton du widget, pied de
 * page, deux endroits de l'accueil, deux de l'espace participant) : le changer
 * dans **Réglages › Agents IA** n'en changeait aucun. Même travers que le
 * « UK » dessiné en dur dans la salle de visio - c'est ce qui fait mentir la
 * configuration.
 */
it('nomme l’assistante publique « Peace » par défaut', function () {
    expect(AiAgents::publicName())->toBe('Peace');

    $public = AiAgents::publicAgent();
    expect($public)->not->toBeNull();
    expect($public['audience'])->toBe('participants');

    // ⚠️ La CLÉ reste `gesco` : c'est un identifiant, enregistré tel quel dans
    // les réglages des installations existantes. Seul le nom s'affiche.
    expect($public['key'])->toBe('gesco');
});

it('fait suivre le nom réglé à toutes les surfaces publiques', function () {
    $agents = AiAgents::all();
    foreach ($agents as $i => $agent) {
        if (($agent['audience'] ?? '') === 'participants') {
            $agents[$i]['name'] = 'Awa';
        }
    }
    AiAgents::save($agents);

    expect(AiAgents::publicName())->toBe('Awa');

    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('Awa');
    // Le nom précédent ne doit subsister nulle part : s'il reste, c'est qu'un
    // libellé est de nouveau écrit en dur.
    expect($html)->not->toContain('Peace');
    expect($html)->not->toContain('Gesco');
});

/**
 * ⚠️ La garde de fond : **aucune vue ne réécrit le nom**. Les identifiants de
 * code (`GescoController`, la route `public.gesco`, la classe `AskGesco`, la
 * clé `gesco`) sont exclus - ce sont des noms de choses, pas des libellés.
 */
it('ne laisse aucune vue ni aucune action écrire le nom de l’assistante en dur', function () {
    $fautifs = [];

    // ⚠️ `app/` AUSSI, pas seulement les vues : une première version ne lisait
    // que `resources/views`, et le message « L'assistance Gesco est
    // momentanément indisponible » d'`AskGesco` lui a échappé - c'est pourtant
    // une phrase que le visiteur lit. Trouvé parce que deux cas existants sont
    // tombés, pas parce que la garde l'a vu.
    $racines = [resource_path('views'), app_path()];

    /** @var list<SplFileInfo> $fichiers */
    $fichiers = [];
    foreach ($racines as $racine) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racine)) as $f) {
            $fichiers[] = $f;
        }
    }

    foreach ($fichiers as $fichier) {
        if ($fichier->getExtension() !== 'php') {
            continue;
        }
        // ⚠️ `AiAgents` est la SOURCE : c'est là que le nom par défaut est
        // écrit, et il faut bien qu'il le soit quelque part.
        if (str_ends_with(str_replace('\\', '/', (string) $fichier->getRealPath()), 'app/Support/AiAgents.php')) {
            continue;
        }

        foreach (file((string) $fichier->getRealPath(), FILE_IGNORE_NEW_LINES) ?: [] as $i => $ligne) {
            if (! str_contains($ligne, 'Gesco') && ! str_contains($ligne, 'Peace')) {
                continue;
            }
            // Les COMMENTAIRES peuvent nommer l'agent : ils expliquent, ils
            // n'affichent rien. Seules les chaînes montrées comptent - y
            // compris les commentaires de FIN DE LIGNE, qu'un premier jet
            // laissait passer pour des libellés.
            $nu0 = ltrim($ligne);
            if (str_starts_with($nu0, '//') || str_starts_with($nu0, '*') || str_starts_with($nu0, '/*') || str_starts_with($nu0, '{{--')) {
                continue;
            }
            $ligne = (string) preg_replace('#//.*$#', '', $ligne);
            if (! str_contains($ligne, 'Gesco') && ! str_contains($ligne, 'Peace')) {
                continue;
            }
            // Identifiants : route, contrôleur, classe, clé, nom de fichier.
            $nu = str_replace(
                ['public.gesco', 'gesco.ask', 'GescoController', 'AskGesco', 'gesco-widget', 'gescoChat', 'gesco-chat', "'gesco'"],
                '',
                $ligne
            );
            if (! str_contains($nu, 'Gesco') && ! str_contains($nu, 'Peace')) {
                continue;
            }

            $relatif = str_replace('\\', '/', (string) $fichier->getRealPath());
            $relatif = substr($relatif, strlen(str_replace('\\', '/', base_path())) + 1);
            $fautifs[] = $relatif.':'.($i + 1).'  '.trim($ligne);
        }
    }
    sort($fautifs);

    expect($fautifs)->toBe([]);
});
