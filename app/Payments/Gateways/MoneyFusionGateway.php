<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;

/**
 * MoneyFusion (Fusion Pay) — passerelle mobile money / carte par appel direct à
 * l'API de l'application marchande.
 *
 * ÉCHAFAUDAGE : l'appel API réel s'active dès que l'URL d'API est configurée
 * (config/services.php → moneyfusion.api_url, renseignée par MONEYFUSION_API_URL).
 * Pas de sandbox public → à valider par un vrai paiement de petit montant.
 */
class MoneyFusionGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'moneyfusion';
    }

    public function label(): string
    {
        return 'Mobile money / carte (MoneyFusion)';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.moneyfusion.api_url'));
    }

    public function initiate(Payment $payment): array
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('MoneyFusion n’est pas configuré (URL d’API manquante).');
        }

        // TODO (implémentation réelle) : POST vers config('services.moneyfusion.api_url')
        // avec { totalPrice, article, personal_Info, numeroSend, nomclient, return_url },
        // récupérer l'URL de paiement hébergée, puis vérifier le statut au retour /
        // via le webhook /webhooks/moneyfusion. Voir la passation uk-colloques.
        throw new \RuntimeException('Appel API MoneyFusion à implémenter avec les identifiants marchands.');
    }
}
