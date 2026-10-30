<?php

namespace App\Payments\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

/**
 * Passerelle de paiement en ligne : sait vérifier le statut d'un paiement auprès
 * du prestataire et interpréter une notification (webhook) de façon sûre.
 */
interface OnlineGateway extends PaymentGateway
{
    /**
     * Le LEXIQUE des opérateurs mobile money du prestataire : clé = le code qu'il
     * emploie, valeur = le libellé qu'on en montre (⚠️ jamais le nom de
     * l'agrégateur). Vide si la passerelle n'en nomme aucun.
     *
     * ⚠️ **Il sert à LIRE, plus à choisir.** Le payeur ne désigne plus son
     * opérateur chez nous : la passerelle qu'il a retenue EST le moyen de
     * paiement, et c'est la page du prestataire qui propose ce que le compte
     * marchand accepte. Cette liste ne survit que pour traduire le `mode` que le
     * prestataire RAPPORTE sur un encaissement abouti ({@see self::details()}),
     * là où un code brut ne dirait rien au gestionnaire ni au reçu.
     *
     * @return array<string, string>
     */
    public function mobileOperators(): array;

    /**
     * Interroge le prestataire et renvoie un statut normalisé.
     *
     * @return 'paid'|'pending'|'failed'|'refunded'
     */
    public function verify(Payment $payment): string;

    /**
     * Ce que le prestataire a ENREGISTRÉ pour un encaissement abouti : l'opérateur
     * réellement employé, sa référence, le numéro qui a payé, ce qui a été débité.
     * Tableau vide si la passerelle n'en sait rien.
     *
     * ⚠️⚠️ **CE N'EST PAS DE LA DÉCORATION.** Le payeur n'est pas toujours le
     * participant - mesuré en production : inscription au nom d'un numéro Togocel,
     * règlement effectué depuis un autre numéro, chez Moov. Sans ces lignes, un
     * paiement contesté ne se raccroche à rien : ni à l'opérateur, ni au téléphone
     * qui a été débité, ni à la référence que le payeur lit sur son relevé.
     *
     * ⚠️ **Rien de secret n'en sort** : ni le jeton de paiement, ni l'URL du reçu
     * du prestataire, qui en porte un. Ce tableau finit en base et sous les yeux
     * d'un gestionnaire.
     *
     * @return array<string, scalar>
     */
    public function details(Payment $payment): array;

    /**
     * Vérifie l'authenticité d'une notification et en extrait la référence et le
     * statut. Retourne null si la signature est invalide (fail-closed).
     *
     * @return array{reference: string, status: 'paid'|'pending'|'failed'|'refunded'}|null
     */
    public function parseWebhook(Request $request): ?array;
}
