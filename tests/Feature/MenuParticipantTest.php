<?php

use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * **Le menu de l'espace participant suit la règle posée côté admin.**
 *
 * `bb3a9206` avait recatégorisé le menu du back-office en cinq groupes
 * cohérents, **chacun masqué si aucun de ses liens n'est accessible**, avec des
 * icônes différenciées. Le menu participant mélangeait encore six entrées sous
 * « Mon dossier » et annonçait une catégorie « Colloque » qui contenait des
 * liens de plateforme.
 *
 * Il porte maintenant : Tableau de bord (hors catégorie), **Ma participation**,
 * **Mes fonctions**, **L'événement**, **La plateforme**, **Mon compte**.
 */

/** Le menu latéral, tel que le participant le reçoit. */
function menuParticipant(User $account): string
{
    $html = (string) test()->actingAs($account, 'account')
        ->get(route('account.dashboard'))
        ->assertOk()
        ->getContent();

    $nav = (string) Str::between($html, '<nav class="ukc-sidebar-nav">', '</nav>');

    // ⚠️ Si l'extraction casse, tout le fichier passerait au vert sur du vide.
    expect($nav)->not->toBeEmpty();

    return $nav;
}

/** Les intitulés de catégorie, dans leur ordre d'apparition. */
function categoriesDuMenu(string $nav): array
{
    preg_match_all('/<span class="nav-label">(.*?)<\/span>/s', $nav, $m);

    return array_map(fn (string $t) => trim(html_entity_decode($t)), $m[1]);
}

/** Le texte VISIBLE du menu : les libellés, sans les URL. */
function texteDuMenu(string $nav): string
{
    preg_match_all('/<span class="(?:txt|nav-label)">(.*?)<\/span>/s', $nav, $m);

    return html_entity_decode(implode(' | ', array_map('trim', $m[1])));
}

/**
 * Un compte participant, distinct à chaque appel.
 *
 * ⚠️ Le nom porte « DuMenu » à dessein : les fonctions d'un fichier de cas sont
 * GLOBALES pour toute la suite. Un `compteParticipant()` existe déjà dans
 * `ParticipantDashboardTest`, et la redéclaration fait tomber la suite entière
 * sur une erreur fatale qui ne dit rien du sujet testé.
 */
function compteDuMenu(string $suffixe): User
{
    $account = User::create([
        'name' => 'Participant '.$suffixe,
        'first_name' => 'Participant', 'last_name' => Str::upper($suffixe),
        'email' => 'menu-'.$suffixe.'@example.tg', 'password' => 'pass12345',
        'grade' => 'Doctorant', 'affiliation' => 'Université de Kara',
        'phone' => '+2289000'.substr(md5($suffixe), 0, 4),
    ]);
    $account->markEmailAsVerified();

    return $account;
}

/** Un compte participant rattaché à un événement publié. */
function participantAvecEvenement(): User
{
    $account = compteDuMenu('avec');

    $serie = EventSeries::create(['name' => 'Série menu', 'slug' => 'serie-menu']);
    /** @var Event $event */
    $event = $serie->events()->create([
        'title' => 'Colloque menu',
        'year' => (int) now()->addMonths(2)->format('Y'),
        'status' => 'open',
        'slug' => 'colloque-menu',
        'submission_open' => true,
        'date_start' => now()->addMonths(2),
        'date_end' => now()->addMonths(2)->addDays(2),
        'settings' => [],
    ]);

    Participant::create(['user_id' => $account->id, 'event_id' => $event->id]);

    return $account;
}

it('range le menu participant en catégories, dans l’ordre', function () {
    $nav = menuParticipant(participantAvecEvenement());

    expect(categoriesDuMenu($nav))->toBe([
        'Ma participation',
        'L’événement',
        'La plateforme',
        'Mon compte',
    ]);

    // « Tableau de bord » reste HORS catégorie, en tête, comme côté admin.
    expect(Str::before($nav, '<span class="nav-label">'))->toContain('Tableau de bord');
});

/**
 * ⚠️ **La règle du menu admin, mot pour mot : « chacune masquée si aucun lien
 * n'est accessible ».** Un intitulé seul est pire qu'une absence - il promet une
 * section vide.
 */
it('ne laisse jamais un intitulé de catégorie sans lien', function () {
    foreach ([participantAvecEvenement(), compteDuMenu('sans')] as $account) {
        $nav = menuParticipant($account);

        // Chaque tronçon qui suit un intitulé doit contenir au moins un lien.
        $troncons = preg_split('/<span class="nav-label">.*?<\/span>/s', $nav);
        array_shift($troncons);   // ce qui précède le premier intitulé

        $vides = [];
        foreach (categoriesDuMenu($nav) as $i => $titre) {
            if (! str_contains((string) ($troncons[$i] ?? ''), '<a ')) {
                $vides[] = $titre;
            }
        }

        expect($vides)->toBe([]);
    }
});

/**
 * Le cas qui donne son sens à la garde : sans événement suivi, « L'événement »
 * n'a plus aucun lien - la catégorie entière disparaît.
 */
it('masque « L’événement » quand le compte n’en suit aucun', function () {
    $sansEvenement = compteDuMenu('seul');

    $nav = menuParticipant($sansEvenement);

    expect(categoriesDuMenu($nav))->toBe([
        'Ma participation',
        'La plateforme',
        'Mon compte',
    ]);
    expect($nav)->not->toContain('Page de l’événement');

    // ⚠️ « Déroulement » survit : sa route est globale, d'où son rattachement à
    // « La plateforme » plutôt qu'à « L'événement » - sans quoi il resterait
    // orphelin sous un intitulé masqué.
    expect($nav)->toContain('Déroulement');
});

/**
 * ⚠️ Deux liens voisins qui portent la même icône se confondent dans le menu
 * replié (`ukc-compact`), où l'icône est TOUT ce qui reste. Le commit admin
 * parlait d'« icônes différenciées » : on l'éprouve ici.
 */
it('ne répète aucune icône dans le menu', function () {
    $nav = menuParticipant(participantAvecEvenement());

    // Les icônes rendues portent leur nom en classe ou en chemin ; on compte les
    // <svg> par leur contenu, qui est unique à chaque icône du jeu.
    preg_match_all('/<svg\b[^>]*>(.*?)<\/svg>/s', $nav, $m);
    $formes = array_map(fn (string $s) => md5(preg_replace('/\s+/', '', $s)), $m[1]);

    expect(count($formes))->toBeGreaterThan(10);

    $doublons = array_values(array_filter(array_count_values($formes), fn (int $n) => $n > 1));

    expect($doublons)->toBe([]);
});

/**
 * Le menu participant avait échappé au renommage « colloque » -> « événement » :
 * `VocabulairePublicTest` ne balaie que le site public, pas les gabarits de
 * l'espace connecté.
 */
it('dit « événement », pas « colloque », dans le menu', function () {
    $texte = texteDuMenu(menuParticipant(participantAvecEvenement()));

    // ⚠️ On éprouve le TEXTE VISIBLE, pas le HTML : la route `/colloque/{slug}`
    // est délibérément conservée (des liens sont déjà partis par e-mail), et
    // elle apparaît dans chaque `href`. Un balayage du HTML brut interdirait
    // donc une URL qu'on veut garder.
    expect(mb_strtolower($texte))->not->toContain('colloque');

    expect($texte)->toContain('L’événement');
    expect($texte)->toContain('Page de l’événement');
});

/** Le site est bilingue : les intitulés neufs ont leur traduction. */
it('traduit les intitulés de catégorie', function () {
    $en = json_decode((string) file_get_contents(base_path('lang/en.json')), true);

    $manquantes = [];
    foreach (['Ma participation', 'Mes fonctions', 'L’événement', 'La plateforme', 'Mon compte'] as $cle) {
        if (! array_key_exists($cle, $en)) {
            $manquantes[] = $cle;
        }
    }

    expect($manquantes)->toBe([]);
});
