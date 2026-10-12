<?php

use Illuminate\Support\Str;

/**
 * **Le site public dit « événement », pas « colloque ».**
 *
 * La plateforme gère des colloques, des séminaires ET des journées
 * scientifiques (`EventType`) : « Colloques à venir » annonçait donc moins que
 * ce que le site contient, et contredisait le modèle typé.
 *
 * ⚠️ « Colloque » reste légitime à deux endroits, et seulement là : quand il
 * désigne **le type** (l'énumération « séminaires, colloques et journées
 * scientifiques », le filtre des archives), et dans les **identifiants** (la
 * route `/colloque/{slug}`, les classes CSS, le partial de pied de page). Ce
 * sont des noms de choses, pas des libellés.
 */

/** Les vues du site public, celles que le visiteur voit. */
function vuesPubliques(): array
{
    return array_values(array_filter(array_merge(
        (array) glob(resource_path('views/public/*.blade.php')),
        (array) glob(resource_path('views/partials/public-*.blade.php')),
        [resource_path('views/home.blade.php')],
        [resource_path('views/components/ukc/gesco-widget.blade.php')],
    )));
}

it('n’emploie plus « colloque » comme mot générique sur le site public', function () {
    // ⚠️ Liste d'exception EXPLICITE, chacune avec sa raison : une règle qui
    // interdirait le mot partout effacerait aussi le TYPE, qui s'appelle bien
    // « colloque ».
    $permises = [
        // L'énumération des types : c'est ce que la plateforme gère.
        'La plateforme des <em>séminaires, colloques et journées scientifiques</em> de l’Université de Kara',
        'Plateforme institutionnelle de l’Université de Kara pour la gestion des séminaires, colloques et journées scientifiques.',
        'La DRI pilote la politique scientifique de l’Université de Kara et coordonne les colloques, séminaires et journées scientifiques organisés sur le campus et avec les partenaires.',
        // Le filtre des archives et la pastille de type portent SUR le type :
        // un evenement est un colloque, une journee ou un seminaire.
        'Colloques',
        'Colloque',
        // Le bouton de la section : il mène aux archives, qui portent ce nom.
        'Toutes les archives des colloques',
    ];

    $fautives = [];

    foreach (vuesPubliques() as $vue) {
        $src = (string) file_get_contents($vue);
        preg_match_all('/(?:__|trans_choice)\(\s*\x27((?:[^\x27\x5c]|\x5c.)*)\x27/u', $src, $m);

        foreach (array_unique($m[1]) as $brut) {
            $cle = str_replace(['\\\'', '\\\\'], ['\'', '\\'], $brut);
            if (! Str::contains(mb_strtolower($cle), 'colloque')) {
                continue;
            }
            if (in_array($cle, $permises, true)) {
                continue;
            }
            $fautives[] = basename($vue).'  '.Str::limit($cle, 70);
        }
    }
    sort($fautives);

    expect($fautives)->toBe([]);
});

/**
 * Le pendant côté rendu : les trois titres que l'utilisateur a signalés.
 */
it('intitule les sections de l’accueil en « événements »', function () {
    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('Colloques à venir');
    expect($html)->not->toContain('Colloques passés');
    expect($html)->not->toContain('Colloque en cours');

    // ⚠️ L'énumération des types reste : la plateforme gère bien des colloques.
    expect($html)->toContain('séminaires, colloques et journées scientifiques');
});

/**
 * ⚠️ **Le site public est bilingue de bout en bout.** Renommer des libellés
 * crée des clés neuves : sans traduction, la version anglaise redevient un
 * panachage. Ce cas lit les sources, là où l'oubli se voit sans parler anglais.
 */
it('ne laisse aucune chaîne du site public sans traduction anglaise', function () {
    $en = json_decode((string) file_get_contents(base_path('lang/en.json')), true);

    $cles = [];
    foreach (vuesPubliques() as $vue) {
        $src = (string) file_get_contents($vue);
        preg_match_all('/(?:__|trans_choice)\(\s*\x27((?:[^\x27\x5c]|\x5c.)*)\x27/u', $src, $m);
        foreach ($m[1] as $brut) {
            $cles[str_replace(['\\\'', '\\\\'], ['\'', '\\'], $brut)] = basename($vue);
        }
    }

    // Si ce nombre s'effondre, c'est l'extraction qui est cassée.
    expect(count($cles))->toBeGreaterThan(150);

    $manquantes = [];
    foreach ($cles as $cle => $vue) {
        if (! array_key_exists($cle, $en)) {
            $manquantes[] = Str::limit($cle, 60).'  ('.$vue.')';
        }
    }
    sort($manquantes);

    expect($manquantes)->toBe([]);
});
