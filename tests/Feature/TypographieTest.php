<?php

use App\Models\Event;
use App\Models\EventSeries;
use Illuminate\Support\Str;

/**
 * **Les caractères qu'on ne tape pas au clavier ne s'écrivent pas dans le code.**
 *
 * Le **point médian** U+00B7 servait de séparateur dans une cinquantaine
 * d'écrans (« Chrome - Windows », « PDF - Déposé le », « d/m/Y - H:i ») ; le
 * **tiret demi-cadratin** U+2013 marquait les plages (« 08:30 - 11:30 »,
 * « 11 - 13 novembre »). Tous deux arrivent par copier-coller et repartent en
 * mojibake dès qu'un maillon perd l'UTF-8. Le séparateur du projet est le
 * tiret simple « - ».
 *
 * ⚠️ **La règle porte sur le CODE, pas sur les données.** Ce qu'une personne
 * saisit - un intitulé d'atelier, une affiliation, un résumé - lui appartient
 * et traverse la plateforme tel quel ; rien ici ne le réécrit. La règle ne vaut
 * donc que pour les fichiers versionnés que nous écrivons.
 *
 * Si un jeu d'essai doit un jour simuler une saisie humaine qui contient l'un
 * de ces caractères (comme `AutoAssignReviewersTest` le fait pour le tiret
 * cadratin), ajoutez-le à `$tolerees` **avec sa raison** - pas le caractère à
 * la dérobée.
 */

/**
 * Les caractères proscrits, écrits par leur point de code pour que ce fichier
 * n'en porte aucun - sans quoi la garde se dénoncerait elle-même.
 *
 * @return array<string, string>
 */
function caracteresProscrits(): array
{
    return [
        'point médian' => "\u{00B7}",
        'tiret demi-cadratin' => "\u{2013}",
    ];
}

/**
 * Ce qui échappe à la règle, et pourquoi. Chaque entrée vaut pour un fichier ;
 * la valeur, quand elle n'est pas `null`, borne la tolérance à une région du
 * fichier (le heredoc nommé), le reste du fichier restant tenu par la règle.
 *
 * @return array<string, string|null>
 */
function tolereesTypographie(): array
{
    return [
        // Résumés, mots-clés et affiliations importés du colloque Eau-Togo :
        // c'est le texte que les AUTEURS ont tapé, y compris « Abomey - Calavi »
        // écrit avec un demi-cadratin. Le réécrire falsifierait des soumissions.
        // ⚠️ Borné au heredoc : le reste du seeder est notre code.
        'database/seeders/ColloqueEauTogoSeeder.php' => 'RESPONSES_JSON',

        // La CLÉ de la table de correspondance transcrit le texte DÉJÀ EN BASE
        // qu'il faut retrouver pour le corriger - sa valeur, elle, porte bien un
        // tiret simple. Changer la clé désactiverait silencieusement la
        // correction de cette phrase.
        'database/migrations/2026_10_02_000100_fix_document_template_typography.php' => null,
    ];
}

/**
 * Les fichiers versionnés que nous écrivons.
 *
 * `lang/vendor/` est hors périmètre : ce sont les traductions publiées d'un
 * paquet tiers, et le catalan emploie légitimement le point médian dans son
 * « l » géminé, qu'on ne peut pas remplacer sans écrire des fautes.
 *
 * @return list<string>
 */
function fichiersDuProjet(): array
{
    $extensions = ['php', 'css', 'js', 'json', 'md', 'neon', 'yml', 'yaml'];

    $racines = [
        base_path('app'),
        base_path('bootstrap'),
        base_path('config'),
        base_path('database'),
        base_path('docs'),
        base_path('lang'),
        base_path('resources'),
        base_path('routes'),
        base_path('tests'),
        base_path('.github'),
    ];

    $fichiers = [];

    foreach ($racines as $racine) {
        if (! is_dir($racine)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($racine, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($it as $f) {
            /** @var SplFileInfo $f */
            if (! $f->isFile() || ! in_array($f->getExtension(), $extensions, true)) {
                continue;
            }
            $rel = str_replace('\\', '/', Str::after($f->getPathname(), base_path().DIRECTORY_SEPARATOR));
            if (Str::startsWith($rel, ['lang/vendor/', 'bootstrap/cache/'])) {
                continue;
            }
            $fichiers[] = $rel;
        }
    }

    foreach (glob(base_path('*.md')) ?: [] as $md) {
        $fichiers[] = basename($md);
    }

    sort($fichiers);

    return $fichiers;
}

/**
 * Le contenu d'un fichier privé de la région tolérée, pour que la règle
 * continue de porter sur tout le reste.
 */
function sansRegionToleree(string $src, ?string $heredoc): string
{
    if ($heredoc === null) {
        return '';
    }

    $lignes = explode("\n", $src);
    $dedans = false;

    foreach ($lignes as $i => $ligne) {
        if (! $dedans && str_contains($ligne, $heredoc." = <<<'")) {
            $dedans = true;
            $lignes[$i] = '';

            continue;
        }
        if ($dedans) {
            // Le heredoc se ferme sur son marqueur seul en début de ligne.
            $dedans = ! preg_match('/^[A-Z_]+;$/', rtrim($ligne));
            $lignes[$i] = '';
        }
    }

    return implode("\n", $lignes);
}

it('n’écrit nulle part dans le code les caractères proscrits', function () {
    $fichiers = fichiersDuProjet();
    $tolerees = tolereesTypographie();

    // ⚠️ Si ce nombre s'effondre, c'est le balayage qui est cassé, pas le code
    // qui est devenu propre.
    expect(count($fichiers))->toBeGreaterThan(600);

    $fautifs = [];

    foreach ($fichiers as $rel) {
        $src = (string) file_get_contents(base_path($rel));

        if (array_key_exists($rel, $tolerees)) {
            $src = sansRegionToleree($src, $tolerees[$rel]);
        }

        foreach (caracteresProscrits() as $nom => $car) {
            if (! str_contains($src, $car)) {
                continue;
            }

            // La première ligne fautive suffit à retrouver l'endroit.
            foreach (explode("\n", $src) as $no => $ligne) {
                if (str_contains($ligne, $car)) {
                    $fautifs[] = $rel.':'.($no + 1).'  ('.$nom.')  '.Str::limit(trim($ligne), 56);

                    break;
                }
            }
        }
    }

    sort($fautifs);

    expect($fautifs)->toBe([]);
});

/**
 * ⚠️ **Les exceptions doivent rester des exceptions.** Sans ce cas, retirer
 * le heredoc du seeder ou supprimer la migration laisserait une tolérance
 * ouverte sur un fichier qui n'en a plus besoin - et le prochain demi-cadratin
 * y passerait inaperçu.
 */
it('ne tolère que des exceptions encore justifiées', function () {
    $inutiles = [];

    foreach (tolereesTypographie() as $rel => $heredoc) {
        if (! is_file(base_path($rel))) {
            $inutiles[] = $rel.' : fichier disparu, retirez la tolérance';

            continue;
        }

        $src = (string) file_get_contents(base_path($rel));

        $porte = false;
        foreach (caracteresProscrits() as $car) {
            if (str_contains($src, $car)) {
                $porte = true;
            }
        }

        if (! $porte) {
            $inutiles[] = $rel.' : plus aucun caractère proscrit, retirez la tolérance';
        }

        // La région nommée doit exister : un heredoc renommé rendrait la
        // tolérance inopérante, et le cas précédent tomberait sans dire pourquoi.
        if ($heredoc !== null && mb_strlen(sansRegionToleree($src, $heredoc)) >= mb_strlen($src)) {
            $inutiles[] = $rel.' : le heredoc « '.$heredoc.' » est introuvable ou vide';
        }
    }

    expect($inutiles)->toBe([]);
});

/** Un événement publié dans la phase voulue, pour que les sections s'affichent. */
function evenementPourTypographie(string $quand): Event
{
    $serie = EventSeries::create(['name' => 'Série typo '.$quand, 'slug' => 'serie-typo-'.$quand]);

    [$debut, $fin] = match ($quand) {
        'ongoing' => [now()->subDay(), now()->addDay()],
        'past' => [now()->subMonths(4), now()->subMonths(4)->addDays(2)],
        default => [now()->addMonths(3), now()->addMonths(3)->addDays(2)],
    };

    return $serie->events()->create([
        'title' => 'Colloque typo '.$quand,
        'year' => (int) $debut->format('Y'),
        'status' => 'open',
        'slug' => 'colloque-typo-'.$quand,
        'submission_open' => false,
        'date_start' => $debut,
        'date_end' => $fin,
        'settings' => [],
    ]);
}

/**
 * Le pendant côté rendu : les sources peuvent être propres et une traduction, un
 * accesseur ou un `format()` réintroduire le caractère à l'exécution.
 *
 * ⚠️ **Les pages doivent être PEUPLÉES.** Premier jet, ce cas rendait l'accueil
 * sur une base vide : les sections de l'accueil ne s'affichant qu'en présence
 * d'événements, il examinait une page presque nue et passait même avec un point
 * médian posé dans la vue. Un sabotage l'a montré. D'où les trois événements, et
 * surtout les points d'appui ci-dessous : si la page redevient vide, le cas
 * tombe au lieu de promettre une couverture qu'il n'a pas.
 */
it('ne rend aucun caractère proscrit sur les pages publiques', function () {
    evenementPourTypographie('upcoming');
    evenementPourTypographie('ongoing');
    $passe = evenementPourTypographie('past');

    $pages = [
        route('home') => ['Événements à venir', 'Événement en cours', 'Événements passés'],
        route('public.archives') => ['Événements passés'],
        route('public.colloque', ['slug' => $passe->slug]) => [$passe->title],
        route('public.deroulement') => ['Programme'],
        route('public.gesco') => ['assistante'],
    ];

    $fautifs = [];

    foreach ($pages as $url => $appuis) {
        $html = (string) $this->get($url)->assertOk()->getContent();
        $page = Str::after($url, (string) config('app.url'));

        // La page a bien rendu ce qu'on vient y chercher. On nomme l'appui
        // manquant : `toContain()` sur un HTML entier recrache la page en
        // hexadécimal, où l'on ne lit rien.
        foreach ($appuis as $appui) {
            if (! str_contains($html, $appui)) {
                $fautifs[] = $page.'  APPUI ABSENT : '.$appui;
            }
        }

        // ⚠️ On relève un EXTRAIT, pas la page : `not->toContain()` sur un HTML
        // entier recrache des dizaines de milliers d'octets en hexadécimal, où
        // l'endroit fautif est introuvable.
        foreach (caracteresProscrits() as $nom => $car) {
            $pos = mb_strpos($html, $car);
            if ($pos !== false) {
                $fautifs[] = $page.'  ('.$nom.')  ...'
                    .trim(mb_substr($html, max(0, $pos - 45), 90)).'...';
            }
        }
    }

    expect($fautifs)->toBe([]);
});

/**
 * Et côté traductions : la clé anglaise est une chaîne à part entière, elle
 * suivait le séparateur français.
 */
it('ne laisse aucun caractère proscrit dans les traductions', function () {
    $fautives = [];

    foreach (glob(base_path('lang/*.json')) ?: [] as $f) {
        /** @var array<string, string> $messages */
        $messages = json_decode((string) file_get_contents($f), true) ?: [];
        foreach ($messages as $cle => $valeur) {
            foreach (caracteresProscrits() as $nom => $car) {
                if (str_contains($cle, $car) || str_contains((string) $valeur, $car)) {
                    $fautives[] = basename($f).'  ('.$nom.')  '.Str::limit($cle, 56);
                }
            }
        }
    }

    expect($fautives)->toBe([]);
});
