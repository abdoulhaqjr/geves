<?php

use App\Models\Attestation;
use App\Models\Event;
use App\Models\EventSeries;
use App\Support\Branding;

/**
 * **La page d'accueil ne montre que ce qu'elle a.**
 *
 * Les sections de données étaient déjà conditionnées ; ce qui ne l'était pas,
 * c'est ce qui les ANNONCE, et deux contenus qui n'en sont pas.
 */

/** Un événement publié, dans la phase voulue. */
function evenementAccueil(string $quand): Event
{
    $serie = EventSeries::create(['name' => 'Série accueil '.$quand, 'slug' => 'serie-'.$quand]);

    // ⚠️ La phase se DÉDUIT des dates (EventPhase) : on les pose, on ne la force pas.
    [$debut, $fin] = match ($quand) {
        'ongoing' => [now()->subDay(), now()->addDay()],
        'past' => [now()->subMonths(4), now()->subMonths(4)->addDays(2)],
        default => [now()->addMonths(3), now()->addMonths(3)->addDays(2)],
    };

    return $serie->events()->create([
        'title' => 'Colloque '.$quand,
        'year' => (int) $debut->format('Y'),
        'status' => 'open',
        'slug' => 'colloque-'.$quand,
        'submission_open' => false,
        'date_start' => $debut,
        'date_end' => $fin,
        'settings' => [],
    ]);
}

/**
 * ⚠️ **Le menu ne promet pas une section absente.** « En cours » et « À venir »
 * visent des ancres de la page d'accueil, et ces sections ne s'affichent qu'en
 * présence d'événements. Sans garde, le menu les offrait toujours : sur
 * geves.univkara.tg, qui n'a aucun colloque en cours, cliquer « En cours » ne
 * faisait rien.
 *
 * Le cas compare les deux côtés au lieu de faire confiance au contrôleur : la
 * règle d'affichage vit dans `HomeController`, celle du menu dans un composeur
 * de vue, et rien n'oblige structurellement les deux à rester d'accord.
 */
it('n’annonce jamais dans le menu une section absente de la page', function () {
    $verifie = function () {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $fautives = [];
        foreach (['en-cours', 'a-venir', 'passes', 'apropos', 'contact'] as $ancre) {
            $lien = str_contains($html, '#'.$ancre.'"');
            $section = str_contains($html, 'id="'.$ancre.'"');
            if ($lien && ! $section) {
                $fautives[] = $ancre;
            }
        }

        return $fautives;
    };

    // Site neuf : aucun événement, donc ni « En cours » ni « À venir ».
    expect($verifie())->toBe([]);
    expect($this->get(route('home'))->getContent())->not->toContain('#en-cours"');

    expect($this->get(route('home'))->getContent())->not->toContain('#passes"');

    // Un colloque en cours : la section apparaît, et le lien avec elle.
    evenementAccueil('ongoing');
    expect($verifie())->toBe([]);
    expect($this->get(route('home'))->getContent())->toContain('#en-cours"');

    // ⚠️ « Passés » visait la PAGE d'archives ; c'est désormais l'ancre de
    // l'accueil, comme « En cours » et « À venir ». Elle suit donc la même
    // règle : pas de section, pas de lien.
    evenementAccueil('past');
    expect($verifie())->toBe([]);
    expect($this->get(route('home'))->getContent())->toContain('#passes"');
});

/**
 * ⚠️ **Quatre zéros n'informent personne.** Sur un site neuf, le bandeau
 * annonçait « 0 événement - 0 colloque - 0 participant - 0 établissement » et
 * donnait une plateforme à l'abandon. On masque le bandeau ENTIER : retirer
 * deux cartes sur quatre laisserait une rangée trouée.
 */
it('masque le bandeau d’indicateurs tant qu’il n’y a rien à compter', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('class="stats"', false);

    evenementAccueil('upcoming');

    $this->get(route('home'))->assertOk()->assertSee('class="stats"', false);
});

/**
 * ⚠️ **Un renseignement qu'on n'a pas ne se remplace pas par sa forme.** La
 * section Contact affichait « +228 XX XX XX XX » au public - le gabarit de
 * saisie, pas un numéro.
 */
it('n’affiche aucun numéro de téléphone fictif', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('XX XX XX XX');
    // L'adresse et les e-mails, eux, sont de vraies valeurs : ils restent.
    expect($html)->toContain('dri@univkara.tg');
});

/**
 * ⚠️ **Une marque ne se redessine pas dans une vue.** La carte « À propos »
 * portait un carré avec les trois lettres « DRI » écrites à la main : le logo
 * téléversé dans Réglages ne s'y appliquait pas. Même travers que le « UK »
 * en dur de la salle de visio - c'est ce qui fait mentir la configuration.
 */
it('pose le logo compact sur la carte « À propos », pas des lettres dessinées', function () {
    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('<div class="mark-lg">DRI</div>');

    // ⚠️ La carte porte désormais le logo LARGE (`.logo-wide`) et non plus le
    // monogramme compact : la section « À propos » a été simplifiée en amont et
    // le panneau a la place du logo complet. Ce qui est éprouvé n'a pas changé -
    // une vraie image, pas des lettres dessinées.
    //
    // ⚠️ On regarde DANS la pastille, pas dans la page : l'adresse du logo livré
    // figure aussi dans la marque de l'en-tête. Un premier jet cherchait cette
    // adresse n'importe où - vidé de son `src`, le logo de la carte disparaissait
    // sans que le cas bronche. Montré par un sabotage.
    expect(preg_match('#<div class="logo-wide">(.*?)</div>#s', $html, $m))->toBe(1);

    $pastille = $m[1];
    expect($pastille)->toContain('<img ');
    expect(preg_match('#<img[^>]+src="([^"]+)"#', $pastille, $img))->toBe(1);
    expect(trim($img[1]))->not->toBeEmpty();
    expect($img[1])->toBe(Branding::wideWhite());
});

/**
 * ⚠️ **« 44 établissements représentés » n'était pas vérifié.** Le chiffre
 * comptait des **chaînes libres distinctes**, pas des établissements : chacun
 * saisit son laboratoire (« Laboratoire Hydrosciences Lavoisier, Université de
 * Bangui »), donc deux laboratoires d'une même université comptaient pour
 * deux, et le total suivait le nombre d'inscrits. Mesuré sur le colloque de
 * l'eau : 44 inscrits, 44 chaînes distinctes - l'accueil affichait « 44
 * participants » à côté de « 44 établissements ».
 *
 * ⚠️ « Colloques & séries » comptait les séries, que le visiteur ne distingue
 * pas des événements, et le mot « colloque » contredit le modèle typé.
 */
it('n’affiche plus les deux indicateurs qui ne mesuraient pas ce qu’ils disaient', function () {
    evenementAccueil('upcoming');

    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect($html)->not->toContain('Établissements représentés');
    expect($html)->not->toContain('Colloques &amp; séries');
    expect($html)->not->toContain('Colloques & séries');
    // Et l'accord du premier indicateur, qui était faux.
    expect($html)->not->toContain('Événements organisées');
    expect($html)->toContain('Événements organisés');
});

/**
 * ⚠️ **Une carte à zéro ne s'affiche pas** - même règle que le bandeau entier.
 * Elle laisserait sinon un « 0 » parmi des chiffres réels, et la grille à
 * quatre colonnes fixes creusait un trou : les cartes sont plafonnées et le
 * reliquat se centre.
 */
it('masque l’indicateur qui vaut zéro', function () {
    evenementAccueil('upcoming');

    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    // Aucune attestation n'a été délivrée : la carte ne doit pas paraître.
    expect(Attestation::count())->toBe(0);
    expect($html)->not->toContain('Attestations délivrées');
    // Mais les indicateurs qui ont de la matière, oui.
    expect($html)->toContain('Événements organisés');
});

/**
 * ⚠️ **La rangée « nom / e-mail » du formulaire de contact débordait sur
 * téléphone.** Deux causes, et il fallait les deux : `min-width: auto` d'un
 * `<input>` vaut sa taille intrinsèque (188px mesurés), donc deux pistes `1fr`
 * refusaient de descendre sous 188 - soit 388px dans une carte de 293 ; et la
 * grille était posée en **style EN LIGNE**, qui l'emporte sur toute règle de
 * feuille, media query comprise.
 */
it('ne pose plus la grille du formulaire de contact en style en ligne', function () {
    // ⚠️ Sans événement « à la une », la section Contact n'affiche qu'un lien
    // e-mail : le formulaire n'existe pas, et le cas passerait à vide.
    evenementAccueil('upcoming');

    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    expect($html)->toContain('class="contact-form-row"');
    expect($html)->not->toContain('style="display:grid;grid-template-columns:1fr 1fr;gap:12px;"');

    $css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents(resource_path('css/public.css')));

    // La piste doit pouvoir se réduire, et passer à une colonne sur téléphone.
    expect($css)->toContain('.contact-form-row > * { min-width: 0; }');
    expect($css)->toContain('.contact-form-row { grid-template-columns: 1fr; }');
});

/**
 * ⚠️ **« Vérifier une attestation » quitte la barre du haut, pas le site.**
 * Cette page existe pour un TIERS - un recruteur qui contrôle une attestation
 * reçue. La retirer de toute navigation la rendrait introuvable à qui n'a pas
 * le lien : elle est reprise dans les liens utiles du pied de page.
 */
it('retire « Vérifier une attestation » du menu du haut sans la rendre introuvable', function () {
    $html = (string) $this->get(route('home'))->assertOk()->getContent();

    $menu = (string) Str::between($html, '<nav class="nav"', '</nav>');
    expect($menu)->not->toBeEmpty();
    expect($menu)->not->toContain(route('attestation.verify.landing'));

    $pied = (string) Str::between($html, '<footer class="site-footer"', '</footer>');
    expect($pied)->toContain(route('attestation.verify.landing'));
});

/**
 * ⚠️ **« Passés » vise l'ancre de l'accueil**, comme « En cours » et « À
 * venir » - et non plus la page d'archives, qui reste atteignable depuis la
 * section elle-même (« Toutes les archives ») et depuis le pied de page.
 */
it('fait pointer « Passés » vers la section de l’accueil', function () {
    evenementAccueil('past');

    $html = (string) $this->get(route('home'))->assertOk()->getContent();
    $menu = (string) Str::between($html, '<nav class="nav"', '</nav>');

    expect($menu)->toContain('#passes"');
    expect($menu)->not->toContain(route('public.archives'));

    // La page d'archives n'est pas pour autant orpheline.
    expect($html)->toContain(route('public.archives'));
});
