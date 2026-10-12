<?php

use App\Models\Event;
use App\Models\EventSeries;
use Illuminate\Support\Str;

/**
 * **Le point médian U+00B7 ne s'écrit pas dans le code du projet.**
 *
 * Il servait de séparateur dans une cinquantaine d'écrans : « Chrome - Windows »,
 * « PDF - Déposé le », « d/m/Y - H:i » s'écrivaient avec un point médian. C'est
 * un caractère qu'on ne tape pas au clavier : il arrive par copier-coller et
 * repart en mojibake dès qu'un maillon perd l'UTF-8. Le séparateur du projet est
 * le tiret « - ».
 *
 * ⚠️ **La règle porte sur le CODE, pas sur les données.** Ce qu'une personne
 * saisit - un intitulé d'atelier, une affiliation, un témoignage - lui
 * appartient et traverse la plateforme tel quel ; rien ici ne le réécrit. La
 * règle ne vaut donc que pour les fichiers versionnés que nous écrivons.
 *
 * Si un jeu d'essai doit un jour simuler une saisie humaine qui contient ce
 * caractère (comme `AutoAssignReviewersTest` le fait pour le tiret cadratin),
 * ajoutez le fichier à `$tolerees` **avec sa raison** - pas le caractère à la
 * dérobée.
 */

/** Le point médian, écrit par son point de code pour que ce fichier n'en porte aucun. */
function pointMedian(): string
{
    return "\u{00B7}";
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

it('n’écrit nulle part le point médian dans le code du projet', function () {
    // Aucun pour l'instant : le caractère n'a aucun emploi légitime ici.
    $tolerees = [];

    $fichiers = fichiersDuProjet();

    // ⚠️ Si ce nombre s'effondre, c'est le balayage qui est cassé, pas le code
    // qui est devenu propre.
    expect(count($fichiers))->toBeGreaterThan(600);

    $fautifs = [];

    foreach ($fichiers as $rel) {
        if (in_array($rel, $tolerees, true)) {
            continue;
        }
        $src = (string) file_get_contents(base_path($rel));
        if (! str_contains($src, pointMedian())) {
            continue;
        }

        // La première ligne fautive suffit à retrouver l'endroit.
        foreach (explode("\n", $src) as $no => $ligne) {
            if (str_contains($ligne, pointMedian())) {
                $fautifs[] = $rel.':'.($no + 1).'  '.Str::limit(trim($ligne), 60);

                break;
            }
        }
    }

    expect($fautifs)->toBe([]);
});

/** Un événement publié dans la phase voulue, pour que les sections s'affichent. */
function evenementPourPointMedian(string $quand): Event
{
    $serie = EventSeries::create(['name' => 'Série pm '.$quand, 'slug' => 'serie-pm-'.$quand]);

    [$debut, $fin] = match ($quand) {
        'ongoing' => [now()->subDay(), now()->addDay()],
        'past' => [now()->subMonths(4), now()->subMonths(4)->addDays(2)],
        default => [now()->addMonths(3), now()->addMonths(3)->addDays(2)],
    };

    return $serie->events()->create([
        'title' => 'Colloque pm '.$quand,
        'year' => (int) $debut->format('Y'),
        'status' => 'open',
        'slug' => 'colloque-pm-'.$quand,
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
it('ne rend aucun point médian sur les pages publiques', function () {
    evenementPourPointMedian('upcoming');
    evenementPourPointMedian('ongoing');
    $passe = evenementPourPointMedian('past');

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

        // La page a bien rendu ce qu'on vient y chercher. On nomme l'appui
        // manquant : `toContain()` sur un HTML entier recrache la page en
        // hexadécimal, où l'on ne lit rien.
        foreach ($appuis as $appui) {
            if (! str_contains($html, $appui)) {
                $fautifs[] = Str::after($url, config('app.url')).'  APPUI ABSENT : '.$appui;
            }
        }

        // ⚠️ On relève un EXTRAIT, pas la page : `not->toContain()` sur un HTML
        // entier recrache des dizaines de milliers d'octets en hexadécimal, où
        // l'endroit fautif est introuvable.
        $pos = mb_strpos($html, pointMedian());
        if ($pos !== false) {
            $fautifs[] = Str::after($url, config('app.url')).'  ...'
                .trim(mb_substr($html, max(0, $pos - 45), 90)).'...';
        }
    }

    expect($fautifs)->toBe([]);
});

/**
 * Et côté traductions : la clé anglaise est une chaîne à part entière, elle
 * suivait le séparateur français.
 */
it('ne laisse aucun point médian dans les traductions', function () {
    $fautives = [];

    foreach (glob(base_path('lang/*.json')) ?: [] as $f) {
        /** @var array<string, string> $messages */
        $messages = json_decode((string) file_get_contents($f), true) ?: [];
        foreach ($messages as $cle => $valeur) {
            if (str_contains($cle, pointMedian()) || str_contains((string) $valeur, pointMedian())) {
                $fautives[] = basename($f).'  '.Str::limit($cle, 60);
            }
        }
    }

    expect($fautives)->toBe([]);
});
