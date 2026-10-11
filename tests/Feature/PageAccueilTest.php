<?php

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
    return $serie->events()->create([
        'title' => 'Colloque '.$quand,
        'year' => (int) now()->format('Y'),
        'status' => 'open',
        'slug' => 'colloque-'.$quand,
        'submission_open' => false,
        'date_start' => $quand === 'ongoing' ? now()->subDay() : now()->addMonths(3),
        'date_end' => $quand === 'ongoing' ? now()->addDay() : now()->addMonths(3)->addDays(2),
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
        foreach (['en-cours', 'a-venir', 'apropos', 'contact'] as $ancre) {
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

    // Un colloque en cours : la section apparaît, et le lien avec elle.
    evenementAccueil('ongoing');
    expect($verifie())->toBe([]);
    expect($this->get(route('home'))->getContent())->toContain('#en-cours"');
});

/**
 * ⚠️ **Quatre zéros n'informent personne.** Sur un site neuf, le bandeau
 * annonçait « 0 événement · 0 colloque · 0 participant · 0 établissement » et
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

    // ⚠️ On regarde DANS la pastille, pas dans la page : l'adresse du
    // monogramme livré figure aussi dans le `<link rel="apple-touch-icon">` et
    // dans la marque de l'en-tête. Un premier jet cherchait cette adresse
    // n'importe où - vidé de son `src`, le logo de la carte disparaissait sans
    // que le cas bronche. Montré par un sabotage.
    expect(preg_match('#<div class="mark-lg">(.*?)</div>#s', $html, $m))->toBe(1);

    $pastille = $m[1];
    expect($pastille)->toContain('<img ');
    expect(preg_match('#<img[^>]+src="([^"]+)"#', $pastille, $img))->toBe(1);
    expect(trim($img[1]))->not->toBeEmpty();
    expect($img[1])->toBe(Branding::markWhite());
});
