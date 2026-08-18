<?php

namespace App\Sms\Contracts;

/**
 * Passerelle d'envoi de SMS (abstraction, calquée sur les passerelles de
 * paiement) : le fournisseur réel est configurable et ses identifiants ne sont
 * jamais commités. Un fournisseur « log » sert de défaut sûr en développement.
 */
interface SmsProvider
{
    /** Identifiant technique (ex. « log », « http »). */
    public function key(): string;

    /** Libellé lisible. */
    public function label(): string;

    /** Le fournisseur est-il configuré et utilisable ? */
    public function isAvailable(): bool;

    /**
     * Envoie un message court à un numéro (format international recommandé).
     *
     * @throws \RuntimeException en cas d'échec d'envoi
     */
    public function send(string $to, string $message): void;
}
