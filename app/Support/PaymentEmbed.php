<?php

namespace App\Support;

use App\Models\Payment;

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
            'public_key' => (string) ($result['public_key'] ?? ''),
            'environment' => (string) ($result['environment'] ?? 'sandbox'),
            'transaction_id' => (string) ($result['transaction_id'] ?? ''),
            // Porte de sortie : la page hébergée, si le script du prestataire ne
            // charge pas.
            'url' => (string) ($result['url'] ?? ''),
            'verify_url' => route('payment.verify', ['gateway' => $gateway]),
        ];
    }
}
