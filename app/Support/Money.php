<?php

namespace App\Support;

/**
 * L'argent tel qu'on l'ÉCRIT.
 *
 * ⚠️⚠️ **LE CODE ISO ET LE MOT NE SONT PAS LE MÊME OBJET.** La devise est `XOF` :
 * c'est ce que la norme exige, ce que la base stocke, et ce que le prestataire
 * attend (`currency: {iso: XOF}`). Mais personne, ici, n'écrit « 100 XOF » - on
 * écrit **« 100 FCFA »**. Le code est pour les machines, le mot pour les
 * lecteurs, et les confondre donne soit un reçu que personne ne lit comme une
 * somme, soit une transaction refusée par le prestataire.
 *
 * ⚠️ C'est une **correspondance**, pas un réglage : deux zones franc coexistent
 * (UEMOA et CEMAC), elles s'écrivent du même mot et ne portent pas le même code.
 * Un réglage à tenir à la main dériverait du jour où quelqu'un changerait la
 * devise sans toucher au libellé.
 */
final class Money
{
    /** Codes ISO 4217 → ce qu'on écrit à la place. */
    private const WORDS = [
        'XOF' => 'FCFA', // franc CFA BCEAO (UEMOA) - le nôtre
        'XAF' => 'FCFA', // franc CFA BEAC (CEMAC)
    ];

    /**
     * Le mot à écrire pour une devise. Sans argument, celle de la plateforme.
     *
     * ⚠️ Une devise inconnue se rend **telle quelle** : mieux vaut « EUR » que
     * rien du tout sur un montant.
     */
    public static function label(?string $iso = null): string
    {
        $code = mb_strtoupper(trim((string) ($iso ?: config('payments.currency', 'XOF'))));

        return self::WORDS[$code] ?? $code;
    }

    /** Noms de devise pour la somme écrite en toutes lettres : [singulier, pluriel]. */
    private const NOUNS = [
        'XOF' => ['franc CFA', 'francs CFA'],
        'XAF' => ['franc CFA', 'francs CFA'],
        'EUR' => ['euro', 'euros'],
        'USD' => ['dollar', 'dollars'],
    ];

    /**
     * Le nom de la devise, accordé : « francs CFA », « franc CFA ».
     *
     * ⚠️ Une devise inconnue rend son code ({@see self::label()}) : mieux vaut
     * « mille EUR » qu'une somme en lettres sans unité.
     */
    public static function noun(?string $iso = null, float|int $montant = 2): string
    {
        $code = mb_strtoupper(trim((string) ($iso ?: config('payments.currency', 'XOF'))));
        $mots = self::NOUNS[$code] ?? null;

        if ($mots === null) {
            return self::label($code);
        }

        return abs((float) $montant) < 2 ? $mots[0] : $mots[1];
    }

    /** « 15 000 » : le chiffre seul, à la française. */
    public static function amount(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ');
    }

    /** « 15 000 FCFA » : le chiffre et le mot. */
    public static function format(float|int|string|null $amount, ?string $iso = null): string
    {
        return self::amount($amount).' '.self::label($iso);
    }
}
