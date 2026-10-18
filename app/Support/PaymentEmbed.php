<?php

namespace App\Support;

use App\Models\Payment;
use App\Payments\Contracts\OnlineGateway;
use App\Payments\GatewayRegistry;

/**
 * Ce qu'il faut à la page pour afficher le formulaire de paiement EMBARQUÉ du
 * prestataire, au lieu d'envoyer le payeur sur sa page hébergée.
 *
 * ⚠️ **Une seule construction, pour deux parcours** : les frais de participation
 * et l'inscription à une formation aboutissent tous deux ici, et un troisième
 * viendra. Deux assemblages se décaleraient, et le défaut ne se verrait que
 * d'un côté - sur un écran de paiement.
 *
 * ⚠️ **Rien de secret ne passe par là.** La clé est la clé PUBLIQUE du
 * prestataire : elle est faite pour vivre dans le navigateur. Le montant, lui,
 * n'y figure pas - la transaction a été créée côté serveur, et c'est son
 * identifiant que le formulaire reprend. Le payeur ne peut donc pas fixer ce
 * qu'il règle.
 */
final class PaymentEmbed
{
    /**
     * @param  array<string, mixed>  $result  ce que la passerelle a rendu
     * @return array<string, mixed>
     */
    public static function payload(array $result, Payment $payment, string $gateway): array
    {
        return [
            'gateway' => $gateway,
            'payment' => $payment->id,
            // ⚠️ **Deux parcours, une seule fenêtre.** `push` : la demande est
            // partie sur le téléphone, la page attend le verdict du serveur.
            // `form` : le formulaire du prestataire s'ouvre dans la fenêtre. Deux
            // composants se décaleraient, et le défaut ne se verrait que d'un
            // côté - sur un écran de paiement.
            'journey' => ($result['type'] ?? '') === 'push' ? 'push' : 'form',
            'phone' => (string) ($result['phone'] ?? ''),
            'operator' => self::operateur($result, $gateway),
            // ⚠️ **Une fenêtre de paiement DIT ce qu'on règle et combien.** Elle
            // ne portait que « Régler en ligne » : le payeur y arrivait sans
            // savoir ce qu'il engageait, et devait faire confiance à un cadre
            // venu d'ailleurs. C'est le moment du parcours où l'on doute le plus.
            'title' => self::objet($payment),
            'amount' => self::montant($payment),
            'public_key' => (string) ($result['public_key'] ?? ''),
            'environment' => (string) ($result['environment'] ?? 'sandbox'),
            'transaction_id' => (string) ($result['transaction_id'] ?? ''),
            // ⚠️ **Ce que le formulaire embarqué ne devinera pas tout seul** : le
            // pays du payeur (sans quoi il propose les opérateurs du Bénin, quel
            // que soit l'indicatif) et l'opérateur choisi. Les deux viennent de
            // la passerelle, donc de la MÊME construction que la transaction.
            //
            // ⚠️ Le montant, lui, n'y figure toujours pas : la transaction a été
            // créée côté serveur, et c'est son identifiant que le formulaire
            // reprend. Le payeur ne peut pas fixer ce qu'il règle.
            'customer' => is_array($result['customer'] ?? null) ? $result['customer'] : [],
            'mode' => (string) ($result['mode'] ?? ''),
            // Porte de sortie : la page hébergée, si le script du prestataire ne
            // charge pas.
            'url' => (string) ($result['url'] ?? ''),
            'verify_url' => route('payment.verify', ['gateway' => $gateway]),
        ];
    }

    /**
     * L'opérateur, dit **comme le payeur le connaît** - « Togocel T-Money »,
     * pas `togocel`.
     *
     * ⚠️ Le libellé vient de la passerelle, jamais d'une liste recopiée : c'est
     * elle qui sait ce qu'elle propose, et une seconde liste se décalerait de la
     * première au premier opérateur ajouté.
     */
    private static function operateur(array $result, string $gateway): string
    {
        $code = (string) ($result['operator'] ?? '');
        if ($code === '') {
            return '';
        }

        $gw = app(GatewayRegistry::class)->get($gateway);

        return $gw instanceof OnlineGateway ? (string) ($gw->mobileOperators()[$code] ?? '') : '';
    }

    /** Ce que le payeur règle, dit en clair. */
    private static function objet(Payment $payment): string
    {
        $detail = $payment->flow() === 'formation' ? (string) $payment->flowDetail() : '';

        return $detail !== '' ? $detail : __('Frais de participation');
    }

    /**
     * Le montant, écrit comme partout ailleurs sur la plateforme.
     *
     * ⚠️ La devise **stockée** a le dernier mot : `XOF` s'affiche « FCFA »
     * comme dans le reste du projet, mais une autre devise se nomme elle-même
     * plutôt que de passer pour des francs CFA.
     */
    private static function montant(Payment $payment): string
    {
        $devise = mb_strtoupper((string) ($payment->currency ?: 'XOF'));
        $devise = in_array($devise, ['XOF', 'FCFA'], true) ? 'FCFA' : $devise;

        return number_format((float) $payment->amount, 0, ',', ' ').' '.$devise;
    }
}
