<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\CashpayGateway;
use App\Payments\Gateways\FedaPayGateway;
use App\Payments\Gateways\ManualGateway;

/**
 * Registre des passerelles de paiement. Résout la liste des passerelles
 * disponibles (identifiants configurés) et la passerelle par défaut.
 */
class GatewayRegistry
{
    /** @var array<int, PaymentGateway> */
    protected array $gateways;

    public function __construct()
    {
        $this->gateways = [
            new CashpayGateway,
            new FedaPayGateway,
            new ManualGateway,
        ];
    }

    /** @return array<int, PaymentGateway> */
    public function all(): array
    {
        return $this->gateways;
    }

    /** @return array<int, PaymentGateway> Passerelles utilisables (le manuel l'est toujours). */
    public function available(): array
    {
        return array_values(array_filter($this->gateways, fn (PaymentGateway $g) => $g->isAvailable()));
    }

    public function get(string $key): ?PaymentGateway
    {
        foreach ($this->gateways as $gateway) {
            if ($gateway->key() === $key) {
                return $gateway;
            }
        }

        return null;
    }

    /** Passerelle par défaut (réglage), avec repli sur le manuel. */
    public function default(): PaymentGateway
    {
        $key = (string) config('payments.default', 'manual');

        $gateway = $this->get($key);

        return $gateway && $gateway->isAvailable() ? $gateway : new ManualGateway;
    }
}
