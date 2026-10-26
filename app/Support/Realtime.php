<?php

namespace App\Support;

/**
 * Canal temps réel de la plateforme (protocole Pusher, serveur hébergé).
 *
 * Le navigateur reçoit les signaux de nouvelles notifications par un service
 * WebSocket hébergé compatible Pusher. Leur contenu est relu auprès de GEVES.
 *
 * Sans service, la notification enregistrée en base reste disponible au prochain
 * chargement de page.
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
     * Les identifiants et un hôte ou un cluster sont exigés ensemble.
     */
    public static function configured(): bool
    {
        if (config('broadcasting.default') !== 'pusher') {
            return false;
        }

        $c = (array) config('broadcasting.connections.pusher', []);

        return filled($c['key'] ?? null)
            && filled($c['secret'] ?? null)
            && filled($c['app_id'] ?? null)
            && (filled($c['options']['host'] ?? null) || filled($c['options']['cluster'] ?? null));
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
     * Le canal de présence de la plateforme : qui a GEVES ouvert, maintenant.
     *
     * ⚠️⚠️ **CE N'EST PAS LA MÊME MESURE QUE « ACTIF DEPUIS 5 MINUTES ».** La
     * table des sessions dit qui a envoyé une requête récemment ; ce canal dit
     * qui tient une page ouverte à l'instant. C'est la question qu'on pose
     * devant un compteur « en ligne », et elle ne peut venir que du navigateur -
     * aucun comptage côté serveur ne sait qu'un onglet vient de se fermer.
     *
     * ⚠️ **Il ne coûte AUCUNE connexion de plus.** La cloche de notifications
     * ouvre déjà un WebSocket sur chaque page authentifiée ({@see
     * resources/js/app.js}) : le canal s'ajoute à cette connexion-là. Un
     * service hébergé facture les connexions, pas les canaux - ouvrir un
     * second socket doublerait la note pour le même chiffre.
     */
    public static function presenceChannel(): string
    {
        return 'ukc.enligne';
    }

    /**
     * L'identité d'un membre sur un canal de présence.
     *
     * ⚠️⚠️ **LA LISTE DES MEMBRES EST LUE PAR TOUS LES AUTRES MEMBRES.** C'est
     * le protocole : qui rejoint un canal de présence reçoit la liste entière,
     * et chaque participant connecté y est. Publier l'identifiant du compte
     * reviendrait donc à donner à toute la plateforme la liste de QUI est en
     * ligne - sur un site qui organise des évaluations en aveugle, ce n'est pas
     * un détail. On publie une empreinte, qui ne se remonte pas.
     *
     * ⚠️ **Elle est STABLE par personne** : c'est ce qui fait que deux onglets
     * ouverts comptent pour une personne et non pour deux. Le service
     * dédoublonne les membres sur cette valeur.
     */
    public static function presenceMemberId(int|string $userId): string
    {
        return substr(hash_hmac('sha256', (string) $userId, (string) config('app.key')), 0, 32);
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
