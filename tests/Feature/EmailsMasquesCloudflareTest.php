<?php

use Illuminate\Support\Str;

/**
 * **Les adresses e-mail masquées par Cloudflare sont redéchiffrées après un
 * remplacement de région.**
 *
 * ⚠️ Derrière Cloudflare, l'option « Email Address Obfuscation » (Scrape
 * Shield) réécrit toute adresse de la page en
 * `<a class="__cf_email__" data-cfemail="HEX">[email protected]</a>`, et injecte
 * un script qui les redéchiffre dans le navigateur. **Ce script ne tourne
 * qu'une fois, au chargement.** Une région remplacée en AJAX arrive donc
 * masquée et le reste : `/admin/utilisateurs` affichait de vraies adresses au
 * chargement, et « [email protected] » partout dès la première recherche.
 * Constaté sur geves.univkara.tg le 11/10/2026 ; invisible en local, qui n'a
 * pas de proxy devant lui - c'est pourquoi la garde lit la SOURCE.
 */
it('redéchiffre les adresses après chaque remplacement de région', function () {
    $js = (string) file_get_contents(base_path('resources/js/app.js'));

    expect($js)->toContain('function ukcDecodeCfEmails(');
    // Le déchiffrement de Cloudflare : premier octet = clé, le reste en XOR.
    expect($js)->toContain('function ukcCfAdresse(');

    // ⚠️ Les DEUX mécanismes de remplacement du projet doivent l'appeler :
    // `data-ajax` (actions) et `data-filter` (barres de filtres). Oublier l'un
    // des deux laisse la moitié du back-office masquée derrière Cloudflare.
    $manquants = [];
    foreach (['ukcAjaxSubmit' => 'async function ukcAjaxSubmit', 'ukcFiltreApplique' => 'async function ukcFiltreApplique'] as $nom => $debut) {
        $corps = Str::before(Str::after($js, $debut), "\n}\n");

        expect($corps)->not->toBeEmpty("fonction {$nom} introuvable");
        if (! str_contains($corps, 'innerHTML')) {
            continue; // ne remplace plus de région : rien à redéchiffrer
        }
        if (! str_contains($corps, 'ukcDecodeCfEmails(')) {
            $manquants[] = $nom;
        }
    }

    expect($manquants)->toBe([]);
});

/**
 * ⚠️ Le déchiffrement s'applique **avant** `Alpine.initTree` dans chaque
 * région : un composant Alpine peut lire le texte qu'il contient, et il doit
 * voir l'adresse, pas le substitut.
 */
it('redéchiffre avant de réinitialiser Alpine', function () {
    $js = (string) file_get_contents(base_path('resources/js/app.js'));

    foreach (['cur', 'courante'] as $cible) {
        $pos = strpos($js, 'ukcDecodeCfEmails('.$cible.')');
        $alpine = strpos($js, 'Alpine?.initTree?.('.$cible.')');

        expect($pos)->not->toBeFalse("appel manquant pour « {$cible} »");
        expect($alpine)->not->toBeFalse("initTree manquant pour « {$cible} »");
        expect($pos)->toBeLessThan($alpine);
    }
});
