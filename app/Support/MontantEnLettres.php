<?php

namespace App\Support;

/**
 * Le montant écrit **en toutes lettres**, comme sur tout reçu officiel
 * francophone : « cent mille francs CFA ».
 *
 * ⚠️⚠️ **CE N'EST PAS DE L'ORNEMENT.** Un chiffre se retouche d'un trait de
 * stylo - « 10 000 » devient « 100 000 » sans qu'on le voie. La somme en lettres
 * est ce qui fige le reçu, et c'est pour cela que la pratique l'exige depuis
 * toujours. En cas de désaccord entre les deux, c'est **elle** qui fait foi.
 *
 * ⚠️ **Écrit ici plutôt que confié à `NumberFormatter`** (intl, `SPELLOUT`) :
 * l'extension peut manquer sur l'hébergement, et un reçu qui tombe en erreur
 * faute d'une extension serait un reçu qu'on ne peut plus délivrer. Les règles
 * tiennent en cinquante lignes et se vérifient une par une.
 */
final class MontantEnLettres
{
    private const UNITES = [
        0 => 'zéro', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre', 5 => 'cinq',
        6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf', 10 => 'dix', 11 => 'onze',
        12 => 'douze', 13 => 'treize', 14 => 'quatorze', 15 => 'quinze', 16 => 'seize',
    ];

    private const DIZAINES = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante',
        6 => 'soixante', 7 => 'soixante', 8 => 'quatre-vingt', 9 => 'quatre-vingt',
    ];

    /** Le montant et sa devise, en toutes lettres : « cent mille francs CFA ». */
    public static function avecDevise(float|int|string|null $montant, ?string $iso = null): string
    {
        $entier = (int) round((float) $montant);

        return self::nombre($entier).' '.Money::noun($iso, $entier);
    }

    /** Le nombre seul, en toutes lettres. */
    public static function nombre(int $n): string
    {
        if ($n < 0) {
            return 'moins '.self::nombre(-$n);
        }

        if ($n === 0) {
            return 'zéro';
        }

        // Groupes de trois chiffres, du plus fort au plus faible.
        $tranches = [
            [1_000_000_000, 'milliard', true],
            [1_000_000, 'million', true],
            [1_000, 'mille', false],
            [1, '', false],
        ];

        $mots = [];

        foreach ($tranches as [$valeur, $nom, $estNom]) {
            $groupe = intdiv($n, $valeur);
            $n %= $valeur;

            if ($groupe === 0) {
                continue;
            }

            if ($nom === '') {
                $mots[] = self::centaines($groupe, accorde: true);

                continue;
            }

            // ⚠️ « mille » est INVARIABLE : « deux mille », jamais « deux milles ».
            // « million » et « milliard » sont des noms, donc ils s'accordent.
            // Et c'est ce qui décide de l'accord de « cent » et « quatre-vingt »
            // juste avant : « deux cents millions », mais « deux cent mille ».
            $tete = $groupe === 1 && ! $estNom ? '' : self::centaines($groupe, accorde: $estNom);
            $pluriel = $estNom && $groupe > 1 ? 's' : '';

            $mots[] = trim($tete.' '.$nom.$pluriel);
        }

        return implode(' ', array_filter($mots));
    }

    /**
     * Un groupe de 1 à 999.
     *
     * @param  bool  $accorde  « cent » et « quatre-vingt » prennent-ils leur `s` ?
     *                         Non quand « mille » suit (il est invariable).
     */
    private static function centaines(int $n, bool $accorde): string
    {
        $cent = intdiv($n, 100);
        $reste = $n % 100;

        $mots = [];

        if ($cent === 1) {
            $mots[] = 'cent';
        } elseif ($cent > 1) {
            // ⚠️ « deux cents » au pluriel SEULEMENT si rien ne suit dans le groupe.
            $mots[] = self::UNITES[$cent].' cent'.($reste === 0 && $accorde ? 's' : '');
        }

        if ($reste > 0) {
            $mots[] = self::dizaines($reste, $accorde);
        }

        return implode(' ', $mots);
    }

    /** Un nombre de 1 à 99. */
    private static function dizaines(int $n, bool $accorde): string
    {
        if ($n <= 16) {
            return self::UNITES[$n];
        }

        // ⚠️ 17, 18 et 19 se composent (« dix-sept »), là où 11 à 16 ont leur mot
        // propre. Sans cette branche, leur dizaine vaut 1 - qui n'existe pas.
        if ($n <= 19) {
            return 'dix-'.self::UNITES[$n - 10];
        }

        $d = intdiv($n, 10);
        $u = $n % 10;

        // 70-79 et 90-99 se disent « soixante-dix » et « quatre-vingt-dix » :
        // la dizaine reste celle d'en dessous et l'unité monte à 10-19.
        if ($d === 7 || $d === 9) {
            $u += 10;
        }

        $mot = self::DIZAINES[$d];

        if ($u === 0) {
            // ⚠️ « quatre-vingts » prend son `s` seul ; « quatre-vingt mille », non.
            return $d === 8 && $accorde ? $mot.'s' : $mot;
        }

        // ⚠️ « vingt et un », mais « quatre-vingt-un » : le « et » ne vaut que
        // pour les dizaines où l'unité est 1, et jamais pour 81.
        if ($u === 1 && $d !== 8) {
            return $mot.' et '.self::UNITES[1];
        }

        if ($u === 11 && $d === 7) {
            return $mot.' et '.self::UNITES[11];
        }

        return $mot.'-'.($u <= 16 ? self::UNITES[$u] : self::dizaines($u, $accorde));
    }
}
