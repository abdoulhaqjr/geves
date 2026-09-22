<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Configuration des agents IA (persona + garde-fous), stockée dans les réglages
 * (clé `ai.agents`). Chaque agent porte une instruction système (persona), des
 * instructions POSITIVES (toujours faire) et NÉGATIVES (ne jamais faire), et un
 * public cible. Les agents d'administration ne font que **proposer** : la
 * décision finale reste humaine. Consommé par le chat Gesco et le copilote admin.
 *
 * @phpstan-type Agent array{key: string, name: string, audience: string, enabled: bool, avatar: string, persona: string, positive: string, negative: string}
 */
class AiAgents
{
    /**
     * Agents fournis par défaut si rien n'est configuré.
     *
     * @return list<Agent>
     */
    public static function defaults(): array
    {
        return [
            [
                'key' => 'gesco',
                'name' => 'Gesco',
                'audience' => 'participants',
                'enabled' => true,
                'avatar' => '',
                'persona' => "Tu es Gesco, l'assistante officielle des colloques de l'Université de Kara (DRI). "
                    .'Tu aides les participants et auteurs sur : soumissions, inscriptions, paiements, programme, '
                    .'créneaux, badges et attestations. Tu es professionnelle, claire, bienveillante et concise. '
                    ."Tu réponds en français et t'appuies uniquement sur les informations du colloque actif et des "
                    .'pages officielles '.config('app.name').'. Tu soignes la présentation : phrases courtes, réponses aérées, '
                    .'listes à puces pour les étapes et gras pour les points clés.',
                'positive' => "- Structurer clairement : une idée par ligne, une liste à puces pour les étapes, le point essentiel en premier.\n"
                    ."- Orienter vers l'espace participant pour les actions personnelles (soumission, paiement, documents).\n"
                    ."- Rappeler les échéances (dates limites de soumission, d'inscription).\n"
                    .'- Proposer les liens utiles (page du colloque, vérification d\'attestation, contact DRI).',
                'negative' => "- Ne jamais inventer de tarifs, dates ou décisions du comité scientifique.\n"
                    ."- Ne jamais valider ou refuser une communication à la place du comité.\n"
                    .'- Ne jamais demander de mot de passe ni de données de paiement dans le chat.',
            ],
            [
                'key' => 'admin',
                'name' => 'Marc',
                'audience' => 'admin',
                'enabled' => true,
                'avatar' => '',
                'persona' => "Tu es Marc, le copilote d'administration de ".config('app.name').'. Tu assistes le personnel de la DRI '
                    .'dans la gestion des éditions, soumissions, créneaux et documents. Tu proposes des actions et '
                    .'des synthèses ; tu ne les exécutes jamais seul. Tu présentes tes réponses de façon lisible : '
                    .'synthèse en tête, listes à puces pour le détail, gras sur les chiffres et points de vigilance.',
                'positive' => "- Structurer : conclusion d'abord, puis le détail en liste à puces, chiffres clés en gras.\n"
                    ."- Proposer des brouillons (e-mails, synthèses, affectations de créneaux) à valider.\n"
                    .'- Signaler les incohérences (paiements manquants, créneaux en conflit).',
                'negative' => "- Ne jamais supprimer, valider une soumission, envoyer un e-mail ou publier sans validation humaine explicite.\n"
                    .'- Ne jamais modifier un paiement ou une attestation de façon autonome.',
            ],
        ];
    }

    /**
     * Agents configurés (repli sur les valeurs par défaut).
     *
     * @return list<Agent>
     */
    public static function all(): array
    {
        $stored = Settings::get('ai.agents');

        if (! is_array($stored) || $stored === []) {
            return self::defaults();
        }

        return array_values(array_map(self::normalize(...), $stored));
    }

    /**
     * Agent par clé, ou null.
     *
     * @return Agent|null
     */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $agent) {
            if ($agent['key'] === $key) {
                return $agent;
            }
        }

        return null;
    }

    /**
     * Enregistre la liste des agents (les clés éventuelles sont réindexées).
     *
     * @param  array<array-key, array<string, mixed>>  $agents
     */
    public static function save(array $agents): void
    {
        Settings::set('ai.agents', array_values(array_map(self::normalize(...), $agents)));
    }

    /**
     * Complète un agent avec des valeurs sûres.
     *
     * @param  array<string, mixed>  $agent
     * @return Agent
     */
    private static function normalize(array $agent): array
    {
        $name = trim((string) ($agent['name'] ?? 'Agent'));

        return [
            'key' => Str::slug((string) ($agent['key'] ?? $name)) ?: 'agent',
            'name' => $name ?: 'Agent',
            'audience' => in_array($agent['audience'] ?? '', ['participants', 'admin'], true) ? $agent['audience'] : 'participants',
            'enabled' => (bool) ($agent['enabled'] ?? true),
            'avatar' => (string) ($agent['avatar'] ?? ''),
            'persona' => (string) ($agent['persona'] ?? ''),
            'positive' => (string) ($agent['positive'] ?? ''),
            'negative' => (string) ($agent['negative'] ?? ''),
        ];
    }
}
