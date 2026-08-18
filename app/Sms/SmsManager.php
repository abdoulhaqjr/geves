<?php

namespace App\Sms;

use App\Sms\Contracts\SmsProvider;
use App\Sms\Providers\HttpSmsProvider;
use App\Sms\Providers\LogSmsProvider;

/**
 * Registre des passerelles SMS : résout le fournisseur par défaut (réglage
 * `sms.default`), avec repli sur le fournisseur « log » si le choix n'est pas
 * disponible. Calqué sur GatewayRegistry (paiements) / MeetingProviderRegistry.
 */
class SmsManager
{
    /** @var array<string, SmsProvider> */
    protected array $providers;

    public function __construct()
    {
        $this->providers = [
            'log' => new LogSmsProvider,
            'http' => new HttpSmsProvider,
        ];
    }

    /** Fournisseur par défaut disponible (repli « log »). */
    public function default(): SmsProvider
    {
        $key = (string) config('sms.default', 'log');
        $provider = $this->providers[$key] ?? null;

        if ($provider && $provider->isAvailable()) {
            return $provider;
        }

        return $this->providers['log'];
    }

    /** Envoie un SMS via le fournisseur par défaut. */
    public function send(string $to, string $message): void
    {
        $this->default()->send($to, $message);
    }

    /** @return array<string, string> clé => libellé (pour les réglages). */
    public function options(): array
    {
        $out = [];
        foreach ($this->providers as $key => $provider) {
            $out[$key] = $provider->label();
        }

        return $out;
    }
}
