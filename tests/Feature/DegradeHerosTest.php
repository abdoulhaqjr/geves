<?php

use Illuminate\Support\Str;

/**
 * **Un seul dégradé de héros : `--ukc-hero`.**
 *
 * Le jeton porte déjà son propre mode d'emploi dans `ukc.css` : une seule
 * source pour tous les héros, pour les harmoniser et pour écarter la teinte
 * quasi noire (#0f1a33) qui traînait dans les anciens dégradés. Un commentaire
 * ne retient personne : trois écrans s'en étaient écartés sans que rien ne
 * tombe. Ces cas lisent les sources, parce que c'est là que la règle se perd.
 */

/** @return array<string, string> chemin relatif => contenu */
function feuillesDeStyle(): array
{
    $out = [];
    foreach ((array) glob(resource_path('css/*.css')) as $chemin) {
        $out['css/'.basename((string) $chemin)] = (string) file_get_contents((string) $chemin);
    }

    return $out;
}

/**
 * Le héros LUI-MÊME, pas ce qui vit dedans : on regarde le DERNIER maillon de
 * chaque sélecteur. ⚠️ Sans cette distinction, la règle attrapait l'avatar de
 * la fenêtre d'assistance (`.gesco-chat .chat-hero .av`), une pastille de 64px
 * qui n'a rien à voir avec la bannière qui la porte.
 */
function estUnHeros(string $selecteur): bool
{
    foreach (explode(',', $selecteur) as $un) {
        $dernier = (string) Str::afterLast(trim(preg_replace('/\s+/', ' ', $un) ?? ''), ' ');
        if (str_contains($dernier, 'hero')) {
            return true;
        }
    }

    return false;
}

it('ne laisse aucun héros poser son propre dégradé', function () {
    // ⚠️ Le héros de « Prise en main » (espace participant) finissait sur un
    // #096b75 PÉTROLE, qui n'est ni le bleu ni le vert de la charte : à côté du
    // tableau de bord, deux identités. Un `var(--ukc-*)` reste permis - ce qu'on
    // refuse, c'est la couleur écrite à la main.
    $fautifs = [];

    foreach (feuillesDeStyle() as $nom => $css) {
        foreach (preg_split('/(?<=\})/', $css) ?: [] as $regle) {
            $selecteur = Str::before($regle, '{');
            if (! str_contains($regle, 'gradient(') || ! estUnHeros($selecteur)) {
                continue;
            }
            preg_match_all('/(?:linear|radial|conic)-gradient\([^;]*\)/', $regle, $m);
            foreach ($m[0] as $degrade) {
                if (preg_match('/#[0-9a-fA-F]{3,8}\b/', $degrade)) {
                    $fautifs[] = $nom.' : '.trim($selecteur).' -> '.Str::limit($degrade, 70);
                }
            }
        }
    }

    expect($fautifs)->toBe([]);
});

it('ne laisse pas le tableau de bord poser son propre dégradé', function () {
    // ⚠️ J'avais ajouté `.tdb-next--calme` (#1f2a52 -> bleu foncé) pour
    // « calmer » la carte d'après-colloque : elle sortait du reste du site. Le
    // moment du parcours se dit par le libellé, l'icône et les boutons - pas
    // par la teinte.
    $css = (string) file_get_contents(resource_path('css/ukc.css'));
    $bloc = substr($css, (int) strpos($css, '.ukc-shell .tdb'));

    preg_match_all('/linear-gradient\([^;]*\)/', $bloc, $m);
    expect($m[0])->not->toBeEmpty(); // la barre d'avancement en a un, en jetons

    $enDur = array_values(array_filter(
        $m[0],
        fn (string $g) => (bool) preg_match('/#[0-9a-fA-F]{3,8}\b/', $g)
    ));

    expect($enDur)->toBe([]);
});

it('n’écrit plus la teinte quasi noire dans un dégradé', function () {
    // ⚠️ #0f1a33 est NOMMÉMENT la teinte que le commentaire de `--ukc-hero`
    // désigne comme à écarter. Elle restait sur l'accueil, en repli des
    // bannières d'événement sans image - l'écran le plus vu du site. Elle garde
    // le droit d'exister en aplat (le fond de la barre latérale) : c'est dans
    // un DÉGRADÉ qu'elle était proscrite.
    $fautifs = [];

    $sources = feuillesDeStyle();
    foreach ((array) glob(resource_path('views/**/*.blade.php')) as $chemin) {
        $sources['views/'.basename((string) $chemin)] = (string) file_get_contents((string) $chemin);
    }
    $sources['views/home.blade.php'] = (string) file_get_contents(resource_path('views/home.blade.php'));

    foreach ($sources as $nom => $src) {
        preg_match_all('/(?:linear|radial|conic)-gradient\([^;)]*(?:\)[^;)]*)*\)/', $src, $m);
        foreach ($m[0] as $degrade) {
            if (stripos($degrade, '#0f1a33') !== false) {
                $fautifs[] = $nom.' -> '.Str::limit($degrade, 70);
            }
        }
    }

    expect($fautifs)->toBe([]);
});
