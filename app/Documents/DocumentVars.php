<?php

namespace App\Documents;

/**
 * Jetons disponibles dans un document (badge, attestation).
 *
 * ⚠️ **Deux syntaxes cohabitent** et doivent répondre aux mêmes valeurs :
 *  - `{cle}` - schéma historique des attestations ;
 *  - `[espace.cle]` - schéma de l'éditeur, celui que proposent la palette du
 *    concepteur et ses modèles livrés.
 *
 * Les contrôleurs ne fournissaient que la forme plate : un modèle dessiné avec
 * la palette (`[recipient.name]`, `[certificate.issued_on]`…) sortait donc avec
 * ses jetons **vides ou affichés tels quels** sur le document délivré, alors que
 * le concepteur, lui, les remplissait. D'où cette table d'alias, **source
 * unique**, appliquée partout où une mise en page est rendue.
 *
 * ⚠️ Un jeton ajouté à la palette du concepteur doit l'être ici aussi, sans quoi
 * il ne vaudra que dans l'aperçu.
 */
class DocumentVars
{
    /**
     * Alias `[espace.cle]` → clé plate correspondante.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'recipient.name' => 'name',
        'recipient.role' => 'role',
        'recipient.institution' => 'institution',
        'recipient.photo' => 'recipient.photo',
        'participant.name' => 'name',
        'participant.role' => 'role',
        'participant.institution' => 'institution',
        'edition.name' => 'event',
        'edition.theme' => 'theme',
        'edition.dates' => 'dates',
        'event.name' => 'event',
        'event.year' => 'year',
        'org.name' => 'org',
        'certificate.uuid' => 'ref',
        'certificate.reference' => 'ref',
        'certificate.issued_on' => 'date',
        'communication.title' => 'communication',
        'communication.type' => 'communication_ref',
        'signatory.name' => 'signatory',
        'signatory.role' => 'signatory_role',
        'badge.ref' => 'ref',
    ];

    /**
     * Complète les valeurs plates par leurs alias `[espace.cle]`.
     *
     * Une valeur déjà fournie sous sa forme longue est conservée : un appelant
     * qui connaît mieux le contexte (la carte du participant, par exemple) garde
     * la main.
     *
     * @param  array<string, mixed>  $vars
     * @return array<string, mixed>
     */
    public static function expand(array $vars): array
    {
        foreach (self::ALIASES as $alias => $flat) {
            if (! array_key_exists($alias, $vars) && isset($vars[$flat])) {
                $vars[$alias] = $vars[$flat];
            }
        }

        return $vars;
    }
}
