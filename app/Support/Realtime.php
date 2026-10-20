<?php

namespace App\Support;

/**
 * Canal temps réel de la plateforme (protocole Pusher, serveur hébergé).
 *
 * ⚠️⚠️ **UN NAVIGATEUR NE PARLE PAS À REDIS.** Redis est un backplane entre
 * processus serveur ; il ne donne aucun point d'entrée WebSocket à une page.
 * Pour que deux rédacteurs se voient taper, il faut un serveur WebSocket joignable
 * depuis le navigateur - et l'hébergement mutualisé de la plateforme ne fait pas
 * tourner un démon à soi. On passe donc par un service HÉBERGÉ qui parle le
 * protocole Pusher (PieSocket, Pusher, Soketi géré…), désigné par son hôte.
 *
 * ⚠️⚠️ **TOUT DOIT FONCTIONNER SANS LUI.** Le temps réel est un CONFORT, pas une
 * condition : sans clés, le document s'ouvre, s'écrit, s'enregistre et garde son
 * historique - seule la simultanéité disparaît, et le contrôle de concurrence
 * par révision empêche alors deux personnes de s'écraser. Même règle que
 * Turnstile, LiveKit et la passerelle de paiement : une intégration absente
 * retire une capacité, elle ne casse pas un écran.
 *
 * ⚠️ **LE SECRET NE DESCEND JAMAIS DANS LA PAGE.** {@see self::clientConfig()}
 * ne rend que la clé publique et l'adresse du serveur ; le secret signe
 * l'autorisation de canal côté serveur (`/broadcasting/auth`) et nulle part
 * ailleurs. Un test de garde le vérifie.
 */
final class Realtime
{
    /**
     * Le temps réel est-il utilisable ?
     *
     * ⚠️ Les trois identifiants sont exigés ENSEMBLE : avec une clé publique
     * seule, la page ouvrirait une connexion que le serveur ne saurait pas
     * autoriser, et l'éditeur attendrait des pairs qui n'arriveraient jamais.
     */
    public static function configured(): bool
    {
        if (config('broadcasting.default') !== 'pusher') {
            return false;
        }

        $c = (array) config('broadcasting.connections.pusher', []);

        return filled($c['key'] ?? null)
            && filled($c['secret'] ?? null)
            && filled($c['app_id'] ?? null);
    }

    /**
     * Ce dont la page a besoin pour se connecter - et rien de plus.
     *
     * @return array{key: string, host: string|null, port: int, scheme: string, cluster: string|null, forceTLS: bool}|null
     */
    public static function clientConfig(): ?array
    {
        if (! self::configured()) {
            return null;
        }

        $c = (array) config('broadcasting.connections.pusher', []);
        $o = (array) ($c['options'] ?? []);
        $scheme = (string) ($o['scheme'] ?? 'https');

        return [
            'key' => (string) $c['key'],
            'host' => filled($o['host'] ?? null) ? (string) $o['host'] : null,
            'port' => (int) ($o['port'] ?? 443),
            'scheme' => $scheme,
            'cluster' => filled($o['cluster'] ?? null) ? (string) $o['cluster'] : null,
            'forceTLS' => $scheme === 'https',
        ];
    }

    /**
     * Nom du canal de présence d'un document.
     *
     * ⚠️ **Une seule définition.** Le nom est écrit à trois endroits - la page,
     * l'autorisation de canal (`routes/channels.php`) et les tests - et trois
     * chaînes construites chacune de leur côté finiraient par se décaler : les
     * rédacteurs se retrouveraient alors chacun dans un canal à eux, sans que
     * rien ne tombe en erreur.
     */
    public static function documentChannel(int $documentId): string
    {
        return 'ukc.doc.'.$documentId;
    }
}
