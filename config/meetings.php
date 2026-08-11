<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fournisseur de visio par défaut
    |--------------------------------------------------------------------------
    |
    | Plateforme utilisée pour la visio interactive des sessions (présentations
    | en ligne). « jitsi » par défaut (gratuit, embarquable). « custom » permet
    | de coller un lien de réunion externe par session.
    |
    */

    'default' => env('MEETING_PROVIDER', 'jitsi'),

    'jitsi' => [
        // Domaine Jitsi. Public par défaut ; renseigner une instance self-host
        // pour activer les rôles authentifiés (JWT).
        'domain' => env('JITSI_DOMAIN', 'meet.jit.si'),

        // Authentification JWT (self-host uniquement). Sans secret, la salle est
        // ouverte (public Jitsi) : le rôle n'est pas imposé côté serveur.
        'app_id' => env('JITSI_APP_ID'),
        'app_secret' => env('JITSI_APP_SECRET'),
        'jwt_ttl' => (int) env('JITSI_JWT_TTL', 7200),

        // Préfixe des noms de salle auto-générés.
        'room_prefix' => env('JITSI_ROOM_PREFIX', 'gesco'),
    ],

];
