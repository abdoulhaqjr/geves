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

    /** Réglages de paiement propres à une édition, ou null. @return array<string, mixed>|null */
    public static function settings(?Edition $edition): ?array
    {
        $p = $edition?->settings['payment'] ?? null;

        return is_array($p) ? $p : null;
    }

    /** L'édition définit-elle au moins un identifiant pour cette passerelle ? */
    public static function configures(Edition $edition, string $gateway): bool
    {
        $creds = self::settings($edition)[$gateway] ?? null;
        if (! is_array($creds)) {
            return false;
        }

        foreach ($creds as $value) {
            if (filled($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Configurations à essayer pour authentifier un webhook entrant : la config
     * globale (null) d'abord, puis chaque édition ayant ses propres identifiants
     * pour cette passerelle. Nécessaire car la signature doit être vérifiée AVANT
     * de savoir à quelle édition le paiement se rattache (un marchand par édition
     * signe avec SON secret).
     *
     * @return list<Edition|null>
     */
    public static function candidates(string $gateway): array
    {
        $editions = Edition::query()
            ->where('settings', 'like', '%"payment"%')
            ->latest('id')
            ->get()
            ->filter(fn (Edition $e) => self::configures($e, $gateway))
            ->values()
            ->all();

        return array_merge([null], $editions);
    }

    public static function apply(?Edition $edition): void
    {
        $p = self::settings($edition);
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

    /**
     * Exécute $fn avec la configuration de l'édition appliquée, puis RESTAURE la
     * configuration précédente. Indispensable quand on essaie plusieurs éditions
     * à la suite (webhook) : sans restauration, les identifiants d'un candidat
     * resteraient actifs pour le suivant (`apply` n'écrase que les champs remplis).
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return T
     */
    public static function using(?Edition $edition, callable $fn): mixed
    {
        $snapshot = [
            'payments.default' => config('payments.default'),
            'services.fedapay' => config('services.fedapay'),
            'services.cashpay' => config('services.cashpay'),
        ];

        try {
            self::apply($edition);

            return $fn();
        } finally {
            Config::set($snapshot);
        }
    }
}
