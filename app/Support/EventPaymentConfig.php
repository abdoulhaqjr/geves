<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Facades\Config;

/**
 * Surcharge, au runtime, la configuration de paiement à partir des réglages
 * PROPRES à un événement (`settings.payment`) : passerelle par défaut + identifiants
 * FedaPay. Les champs laissés VIDES retombent sur la configuration
 * globale (réglages plateforme). À appeler avant de résoudre/utiliser une
 * passerelle pour un paiement rattaché à un événement.
 */
class EventPaymentConfig
{
    /** Clés d'identifiants par passerelle (mêmes que la config globale). */
    public const FEDAPAY_KEYS = ['secret', 'mode', 'webhook_secret'];

    /** Réglages de paiement propres à un événement, ou null. @return array<string, mixed>|null */
    public static function settings(?Event $event): ?array
    {
        $p = $event?->settings['payment'] ?? null;

        return is_array($p) ? $p : null;
    }

    /** L'événement définit-elle au moins un identifiant pour cette passerelle ? */
    public static function configures(Event $event, string $gateway): bool
    {
        $creds = self::settings($event)[$gateway] ?? null;
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
     * globale (null) d'abord, puis chaque événement ayant ses propres identifiants
     * pour cette passerelle. Nécessaire car la signature doit être vérifiée AVANT
     * de savoir à quelle événement le paiement se rattache (un marchand par événement
     * signe avec SON secret).
     *
     * @return list<Event|null>
     */
    public static function candidates(string $gateway): array
    {
        $events = Event::query()
            ->where('settings', 'like', '%"payment"%')
            ->latest('id')
            ->get()
            ->filter(fn (Event $e) => self::configures($e, $gateway))
            ->values()
            ->all();

        return array_merge([null], $events);
    }

    public static function apply(?Event $event): void
    {
        $p = self::settings($event);
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
    }

    /**
     * Exécute $fn avec la configuration de l'événement appliquée, puis RESTAURE la
     * configuration précédente. Indispensable quand on essaie plusieurs événements
     * à la suite (webhook) : sans restauration, les identifiants d'un candidat
     * resteraient actifs pour le suivant (`apply` n'écrase que les champs remplis).
     *
     * @template T
     *
     * @param  callable(): T  $fn
     * @return T
     */
    public static function using(?Event $event, callable $fn): mixed
    {
        $snapshot = [
            'payments.default' => config('payments.default'),
            'services.fedapay' => config('services.fedapay'),
        ];

        try {
            self::apply($event);

            return $fn();
        } finally {
            Config::set($snapshot);
        }
    }
}
