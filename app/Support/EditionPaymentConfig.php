<?php

namespace App\Support;

use App\Models\Edition;
use Illuminate\Support\Facades\Config;

/**
 * Surcharge, au runtime, la configuration de paiement à partir des réglages
 * PROPRES à une édition (`settings.payment`) : passerelle par défaut + identifiants
 * FedaPay / CashPay. Les champs laissés VIDES retombent sur la configuration
 * globale (réglages plateforme). À appeler avant de résoudre/utiliser une
 * passerelle pour un paiement rattaché à une édition.
 */
class EditionPaymentConfig
{
    /** Clés d'identifiants par passerelle (mêmes que la config globale). */
    public const FEDAPAY_KEYS = ['secret', 'mode', 'webhook_secret'];

    public const CASHPAY_KEYS = ['base_url', 'username', 'password', 'client_id', 'client_secret', 'api_key'];

    public static function apply(?Edition $edition): void
    {
        $p = is_array($edition?->settings['payment'] ?? null) ? $edition->settings['payment'] : null;
        if (! $p) {
            return; // aucune config propre : on garde la config globale
        }

        if (filled($p['gateway'] ?? null)) {
            Config::set('payments.default', (string) $p['gateway']);
        }

        foreach (self::FEDAPAY_KEYS as $k) {
            if (filled($p['fedapay'][$k] ?? null)) {
                Config::set("services.fedapay.$k", $p['fedapay'][$k]);
            }
        }
        foreach (self::CASHPAY_KEYS as $k) {
            if (filled($p['cashpay'][$k] ?? null)) {
                Config::set("services.cashpay.$k", $p['cashpay'][$k]);
            }
        }
    }
}
