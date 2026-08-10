<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;

/**
 * FedaPay — mobile money (MTN, Moov, Togocom…) et cartes via page hébergée.
 *
 * ÉCHAFAUDAGE : s'active dès que la clé secrète marchande est configurée
 * (config/services.php → fedapay.secret). Clés test/live séparées.
 */
class FedaPayGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'fedapay';
    }

    public function label(): string
    {
        return 'Mobile money / carte (FedaPay)';
    }

    public function isAvailable(): bool
    {
        return filled(config('services.fedapay.secret'));
    }

    public function initiate(Payment $payment): array
    {
        if (! $this->isAvailable()) {
            throw new \RuntimeException('FedaPay n’est pas configuré (clé secrète manquante).');
        }

        // TODO (implémentation réelle) : créer une transaction FedaPay, générer le
        // token/lien de paiement hébergé, puis vérifier le statut (approved) au
        // retour et via le webhook signé (X-FEDAPAY-Signature).
        throw new \RuntimeException('Appel API FedaPay à implémenter avec la clé marchande.');
    }
}
