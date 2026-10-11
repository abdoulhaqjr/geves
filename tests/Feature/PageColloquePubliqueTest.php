<?php

// openEdition() -> tests/Pest.php

use Illuminate\Support\Str;

/**
 * **La page publique d'un événement : ce qu'elle montre, et ce qu'elle ne
 * montre pas.**
 *
 * Trois travers mesurés sur le colloque de l'eau, tous invisibles en lisant le
 * code et tous bien visibles à l'écran. Ces cas les retiennent.
 */

/** Les feuilles de style publiques, **commentaires retirés**. */
function cssPublic(): string
{
    $css = (string) file_get_contents(resource_path('css/public.css'));

    // ⚠️ Sans ce retrait, les commentaires qui EXPLIQUENT pourquoi `auto-fit`
    // a été abandonné feraient tomber le cas qui l'interdit. Déjà vu une fois,
    // sur le dégradé des héros : un commentaire n'applique rien.
    return (string) preg_replace('#/\*.*?\*/#s', '', $css);
}

/** Le corps de la première règle CSS portant exactement ce sélecteur. */
function regleCss(string $css, string $selecteur): ?string
{
    foreach (explode('}', $css) as $bloc) {
        if (! str_contains($bloc, '{')) {
            continue;
        }
        [$sel, $corps] = explode('{', $bloc, 2);
        if (trim((string) Str::afterLast($sel, "\n")) === $selecteur) {
            return $corps;
        }
    }

    return null;
}

/**
 * ⚠️ **Le barème est une LISTE, pas une grille de cartes.** Mesuré sur six
 * catégories dans une fenêtre de 1280px : la grille donnait une rangée de 4
 * puis une rangée ORPHELINE de 2 ; elle égalisait les hauteurs de rangée et
 * creusait jusqu'à 194px de VIDE dans la carte la plus courte, parce que les
 * catégories couvrent de 1 à 6 grades ; et elle posait les six montants à six
 * abscisses différentes, alors que comparer est la seule chose qu'on fasse
 * devant un barème.
 */
it('rend le barème en liste, chaque catégorie avec son montant et ses grades', function () {
    $event = openEdition(['fees' => [
        ['label' => 'Enseignants et chercheurs', 'amount' => 35000, 'grades' => ['Professeur', 'Maître-assistant']],
        ['label' => 'Doctorants', 'amount' => 10000, 'grades' => ['Doctorant']],
        ['label' => 'Autres participants', 'amount' => 100000, 'grades' => [], 'default' => true],
    ]]);

    $page = $this->get(route('public.colloque', $event->slug))->assertOk();

    $page->assertSee('ukc-fees', false)->assertSee('ukc-fee__amount', false);
    // La forme abandonnée, pour qu'un retour en arrière se voie ici.
    $page->assertDontSee('ukc-price-card', false)->assertDontSee('ukc-pricing', false);

    // Une ligne par catégorie : intitulé, montant, grades couverts.
    foreach (['Enseignants et chercheurs' => '35 000', 'Doctorants' => '10 000', 'Autres participants' => '100 000'] as $libelle => $montant) {
        $page->assertSee($libelle)->assertSee($montant);
    }
    $page->assertSee('Professeur')->assertSee('Maître-assistant')->assertSee('Tout autre grade');

    // ⚠️ Les grades couverts tiennent sur UNE ligne qui s'enroule. En puces,
    // la catégorie qui en couvre six gonflait sa rangée entière - et c'est ce
    // gonflement qui creusait les voisines.
    $page->assertSee('Professeur · Maître-assistant', false);
});

/**
 * ⚠️ **Les montants s'alignent en une colonne unique**, sinon ils ne se
 * comparent pas. Deux conditions, toutes deux dans la feuille de style : une
 * seule colonne de lignes, et une chasse de chiffres fixe - sans
 * `tabular-nums`, les chiffres de « 100 000 » et de « 10 000 » ne tombent pas
 * les uns sous les autres.
 */
it('tient le barème et les ateliers hors des grilles à colonnes', function () {
    $css = cssPublic();

    $bareme = regleCss($css, '.ukc-fees');
    expect($bareme)->not->toBeNull('la règle .ukc-fees a disparu');
    expect($bareme)->not->toContain('auto-fit');
    expect($bareme)->not->toContain('auto-fill');
    expect(regleCss($css, '.ukc-fee__amount'))->toContain('tabular-nums');

    // ⚠️ Même travers, même famille : six ateliers donnaient 4 + 2, donc une
    // rangée à moitié trouée sur la droite. `auto-fit` choisit un nombre de
    // colonnes sans savoir combien d'éléments il reste.
    $ateliers = regleCss($css, '.ukc-themes');
    expect($ateliers)->not->toBeNull('la règle .ukc-themes a disparu');
    expect($ateliers)->not->toContain('auto-fit');
    expect($ateliers)->not->toContain('auto-fill');
});

/**
 * ⚠️ **Un emplacement vide ne se montre pas.** À défaut de visuel téléversé,
 * la page affichait un SUBSTITUT : un bloc coloré de 420px intitulé
 * « BANNIÈRE OFFICIELLE » qui répétait le titre et les dates déjà écrits dans
 * le bandeau, 400px plus haut. Aucun des deux colloques n'ayant téléversé de
 * visuel, c'est ce substitut que voyait tout visiteur.
 */
it('n’affiche pas de bannière de substitution quand aucun visuel n’est téléversé', function () {
    $event = openEdition();
    expect($event->banner_url)->toBeNull();

    $this->get(route('public.colloque', $event->slug))->assertOk()
        ->assertDontSee('ukc-banner-frame', false)
        ->assertDontSee('ukc-banner-placeholder', false)
        ->assertDontSee('id="banniere"', false)
        ->assertDontSee('Bannière officielle');
});

it('affiche la bannière dès qu’un visuel est téléversé, et la respecte coupée', function () {
    $event = openEdition();
    $event->forceFill(['banner_url' => '/storage/editions/banniere.jpg'])->save();

    $this->get(route('public.colloque', $event->slug))->assertOk()
        ->assertSee('ukc-banner-frame', false)
        ->assertSee('/storage/editions/banniere.jpg', false);

    // Le réglage « ne pas afficher » reste décisif, visuel ou non.
    $event->forceFill(['settings' => array_merge($event->settings, ['banner_enabled' => false])])->save();

    $this->get(route('public.colloque', $event->slug))->assertOk()
        ->assertDontSee('ukc-banner-frame', false);
});

/**
 * ⚠️ **La page publique existe aussi en anglais**, et le sélecteur de langue
 * est dans sa propre barre de navigation. Douze chaînes n'avaient pas de
 * traduction, dont six dans le seul barème : en anglais, la section mêlait
 * « Participation fees » et « Grades concernés ». Une page à moitié traduite
 * se lit plus mal qu'une page qui ne l'est pas du tout.
 */
it('ne laisse aucune chaîne de la page publique sans traduction anglaise', function () {
    $src = (string) file_get_contents(base_path('resources/views/public/colloque.blade.php'));

    preg_match_all('/(?:__|trans_choice)\(\s*\x27((?:[^\x27\x5c]|\x5c.)*)\x27/u', $src, $m);

    $cles = [];
    foreach ($m[1] as $brute) {
        $cles[] = str_replace(['\\\'', '\\\\'], ['\'', '\\'], $brute);
    }
    $cles = array_values(array_unique($cles));

    // Si ce nombre s'effondre, c'est l'extraction qui est cassée, pas la page
    // qui serait devenue muette.
    expect(count($cles))->toBeGreaterThan(60);

    $en = json_decode((string) file_get_contents(base_path('lang/en.json')), true);

    $manquantes = [];
    foreach ($cles as $cle) {
        if (! array_key_exists($cle, $en)) {
            $manquantes[] = Str::limit($cle, 60);
        }
    }
    sort($manquantes);

    expect($manquantes)->toBe([]);
});
