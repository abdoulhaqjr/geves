<?php

namespace App\Support;

use App\Payments\Contracts\OnlineGateway;

/**
 * **L'opérateur mobile, déduit du numéro.**
 *
 * Un payeur qui a donné son téléphone a déjà dit son opérateur : le préfixe de
 * son numéro le désigne. Le lui redemander dans une liste de onze entrées, c'est
 * lui faire chercher ce que nous savons - au moment du parcours où l'on
 * abandonne le plus.
 *
 * ⚠️ **C'est une PRÉSOMPTION, jamais un fait.** La portabilité des numéros
 * existe dans la zone : un numéro en 90 peut avoir déménagé chez l'autre
 * opérateur, et aucun plan de numérotation ne le dira jamais. La déduction doit
 * donc rester **visible et modifiable** là où elle sert - jamais appliquée en
 * silence. Transmise au prestataire, elle enverrait le payeur sur le formulaire
 * du mauvais opérateur, sans qu'il comprenne pourquoi.
 *
 * ⚠️ **Préfixe inconnu = rien du tout.** Les plans publiés sont incomplets - le
 * Niger n'attribue publiquement pas la moitié de ses tranches - et ils bougent :
 * le Bénin est passé à dix chiffres le 30 novembre 2024. Ne rien déduire laisse
 * le payeur choisir, ce qui fonctionnait déjà ; deviner le dirigerait vers le
 * mauvais guichet.
 */
final class MobileNetwork
{
    /**
     * Les plans de numérotation, par indicatif pays : réseau => préfixes **à
     * deux chiffres** du numéro national (après normalisation, cf. `national()`).
     *
     * Sources : plans publiés par les régulateurs de la zone (ARCEP Togo, ARCEP
     * Bénin, ARTCI, ARTP, ARCEP Niger) et plans ITU-T, consultés le 2026-10-18.
     *
     * ⚠️ **Des PAIRES, pas un tableau associatif** : PHP transforme une clé
     * `'228'` en **entier**, et `str_starts_with()` attend une chaîne. Un cast
     * au point d'usage ferait marcher le code en faisant mentir le type - c'est
     * la forme déjà retenue par `FedaPayGateway::countryFromE164()`.
     *
     * @var list<array{0: string, 1: array<string, list<string>>}>
     */
    private const PLANS = [
        // Togo - 8 chiffres. 74 à 77 ne sont pas attribués.
        ['228', [
            'togocom' => ['70', '71', '72', '73', '90', '91', '92', '93'],
            'moov-tg' => ['78', '79', '96', '97', '98', '99'],
        ]],
        // Bénin - 10 chiffres depuis le 2024-11-30 (« 01 » posé devant les huit
        // anciens chiffres, qui portent toujours l'opérateur).
        ['229', [
            'mtn-bj' => ['42', '46', '50', '51', '52', '53', '54', '56', '57', '59',
                '61', '62', '66', '67', '69', '90', '91', '96', '97'],
            'moov-bj' => ['45', '55', '58', '60', '63', '64', '65', '68', '94', '95', '98'],
            'celtiis-bj' => ['20', '21', '22', '23', '24', '28', '29', '40'],
        ]],
        // Côte d'Ivoire - 10 chiffres depuis le 2021-01-31.
        ['225', [
            'moov-ci' => ['01'],
            'mtn-ci' => ['05'],
            'orange-ci' => ['07'],
        ]],
        // Sénégal - 9 chiffres. 75 est partagé entre opérateurs de réseau
        // virtuel : il ne désigne donc personne.
        ['221', [
            'free-sn' => ['76'],
            'orange-sn' => ['77', '78'],
            'expresso-sn' => ['70'],
        ]],
        // Niger - 8 chiffres. ⚠️ Plan partiellement publié : beaucoup de
        // tranches n'y figurent pas, et un numéro hors liste ne donne rien.
        ['227', [
            'airtel-ne' => ['86', '96'],
            'moov-ne' => ['74', '93'],
            'zamani-ne' => ['70', '80', '81', '82', '90', '91', '92', '94'],
        ]],
    ];

    /**
     * Le réseau mobile d'un numéro E.164 (`togocom`, `moov-tg`, `mtn-bj`…), ou
     * null si l'indicatif ou le préfixe n'est pas dans les plans connus.
     *
     * ⚠️ Ces identifiants sont les **nôtres**, pas ceux d'un prestataire : à
     * chaque passerelle de traduire vers son vocabulaire
     * ({@see OnlineGateway::operatorFromPhone()}).
     * Mêler les deux ferait d'un changement de prestataire une réécriture des
     * plans de numérotation.
     */
    public static function fromPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        foreach (self::PLANS as [$indicatif, $reseaux]) {
            if (! str_starts_with($digits, $indicatif)) {
                continue;
            }

            $national = self::national($indicatif, substr($digits, strlen($indicatif)));
            $prefixe = substr($national, 0, 2);

            foreach ($reseaux as $reseau => $prefixes) {
                if (in_array($prefixe, $prefixes, true)) {
                    return $reseau;
                }
            }

            // Indicatif connu, préfixe inconnu : on ne cherche pas ailleurs.
            return null;
        }

        return null;
    }

    /**
     * Le numéro national ramené à la forme où son préfixe se lit sur deux
     * chiffres.
     *
     * ⚠️ **Le Bénin a posé « 01 » devant TOUS ses numéros** le 30 novembre 2024,
     * en passant à dix chiffres. Les numéros saisis avant la réforme en portent
     * encore huit : les deux formes désignent le même abonné, et la base
     * contient les deux.
     */
    private static function national(string $indicatif, string $numero): string
    {
        if ($indicatif === '229' && strlen($numero) === 10 && str_starts_with($numero, '01')) {
            return substr($numero, 2);
        }

        return $numero;
    }
}
