<?php

use Illuminate\Support\Str;

/**
 * **Le diamètre de la bulle de l'assistante est un JETON, pas quatre nombres.**
 *
 * ⚠️ Quatre règles s'accrochent à ce diamètre : la bulle elle-même, la
 * mascotte qu'elle contient, le décalage du panneau posé au-dessus d'elle, et
 * le dégagement laissé en bas des pages qui la portent. Écrites en dur, elles
 * se décalent dès qu'on touche au diamètre : le panneau vient chevaucher la
 * bulle, et le bas de page passe dessous. C'est la leçon de `--vtop` et
 * `--edtop` dans la salle de visio et les concepteurs, appliquée ici.
 */

/** Les feuilles de style, **commentaires retirés**. */
function feuillesSansCommentaires(): array
{
    $out = [];
    foreach (['ukc.css', 'public.css'] as $nom) {
        $css = (string) file_get_contents(resource_path('css/'.$nom));
        $out[$nom] = (string) preg_replace('#/\*.*?\*/#s', '', $css);
    }

    return $out;
}

it('déclare le diamètre de la bulle en jeton, avec une valeur réduite sur petit écran', function () {
    $css = feuillesSansCommentaires();

    expect($css['ukc.css'])->toContain('--ukc-cb-fab:');

    // ⚠️ Sur un téléphone, la bulle recouvre une part bien plus grande de
    // l'écran : une seule valeur pour toutes les largeurs ne convient pas.
    $etroit = Str::between($css['ukc.css'], '@media (max-width: 560px)', '}');
    expect($etroit)->toContain('--ukc-cb-fab:');
});

it('fait suivre le jeton à toutes les règles qui en dépendent', function () {
    $css = feuillesSansCommentaires();

    $regles = [
        'public.css' => ['.ukc-cb-fab {', '.ukc-cb-fab .mascot {', '.ukc-cb-panel {', '.ukc-cb-panel.expanded {'],
    ];

    $fautives = [];
    foreach ($regles as $fichier => $selecteurs) {
        foreach ($selecteurs as $sel) {
            $corps = Str::before(Str::after($css[$fichier], $sel), '}');
            if ($corps === '' || ! str_contains($corps, 'var(--ukc-cb-fab)')) {
                $fautives[] = $fichier.'  '.$sel;
            }
        }
    }

    // Le dégagement du bas de page, côté back-office.
    // ⚠️ Il y a DEUX règles (`.ukc-shell:has(…)` et `body:has(…)`), et un
    // `str_contains` sur le fichier entier passait alors qu'une seule avait été
    // remise en dur : la garde ne mordait pas, un sabotage l'a montré. On
    // examine chaque bloc qui parle de la bulle, un par un.
    $blocs = 0;
    foreach (explode('}', $css['ukc.css']) as $bloc) {
        if (! str_contains($bloc, '{') || ! str_contains($bloc, '.ukc-cb-root')) {
            continue;
        }
        [$sel, $corps] = explode('{', $bloc, 2);
        if (! str_contains($corps, 'padding-bottom')) {
            continue;
        }
        $blocs++;
        if (! str_contains($corps, 'var(--ukc-cb-fab)')) {
            $fautives[] = 'ukc.css  '.trim(preg_replace('/\s+/', ' ', $sel) ?? '');
        }
    }
    // Si ce compte tombe à zéro, c'est le découpage qui est cassé, pas la règle
    // qui serait devenue inutile.
    expect($blocs)->toBe(2);

    expect($fautives)->toBe([]);
});

/**
 * ⚠️ **Le panneau se pose AU-DESSUS de la bulle, jamais dessus.** Son décalage
 * et sa hauteur se calculent tous deux à partir du diamètre : un décalage figé
 * à 76px laissait le panneau recouvrir une bulle de 84.
 */
it('garde le panneau au-dessus de la bulle, quel que soit le diamètre', function () {
    $css = feuillesSansCommentaires()['public.css'];

    foreach (['.ukc-cb-panel {', '.ukc-cb-panel.expanded {'] as $sel) {
        $corps = Str::before(Str::after($css, $sel), '}');

        expect($corps)->toContain('bottom: calc(var(--ukc-cb-fab)');
        // La hauteur aussi : sinon un panneau haut dépasse en haut d'écran.
        expect($corps)->toContain('var(--ukc-cb-fab)');
        expect($corps)->not->toContain('100vh - 120px');
    }
});
