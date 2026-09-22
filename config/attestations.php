<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Clé de signature de l'émetteur (Ed25519)
    |--------------------------------------------------------------------------
    |
    | Graine privée Ed25519 (32 octets, encodée base64url), HORS DÉPÔT, définie
    | via la variable d'environnement ATTESTATION_SIGNING_KEY. Générez-la avec
    | `php artisan attestations:keygen`. Vide = certification désactivée : les
    | attestations sont émises sans signature (fail-open), la page de
    | vérification retombe sur le contrôle par registre (existence + révocation).
    |
    | ⚠️ Ne JAMAIS committer cette valeur ni la stocker en base : un vidage de
    | base ou un accès admin ne doit pas exposer la clé privée de l'émetteur.
    |
    */

    'signing_key' => env('ATTESTATION_SIGNING_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Identifiant de clé (kid) actif
    |--------------------------------------------------------------------------
    |
    | Fragment identifiant la clé active dans le document DID (verificationMethod).
    | Combiné à l'issuer DID des réglages d'édition pour former le `kid` du JWS
    | (ex. « did:web:univkara.tg:colloques#key-ed25519-1 »). En cas de rotation,
    | changez ce fragment et conservez les anciennes clés publiques ci-dessous.
    |
    */

    'active_kid' => env('ATTESTATION_KID', 'key-ed25519-1'),

    /*
    |--------------------------------------------------------------------------
    | Clés publiques retirées (rotation)
    |--------------------------------------------------------------------------
    |
    | Clés publiques (32 octets, base64url) d'anciennes graines, indexées par
    | fragment kid. Permettent de continuer à VÉRIFIER les attestations signées
    | avant une rotation, sans jamais re-signer. Format : ['key-ed25519-0' => '…'].
    |
    | @var array<string, string>
    */

    'retired_public_keys' => array_filter(
        (array) (json_decode((string) env('ATTESTATION_RETIRED_KEYS', '{}'), true) ?: [])
    ),

];
