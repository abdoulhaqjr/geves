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

    'default' => env('MEETING_PROVIDER', 'livekit'),

    'livekit' => [
        // Serveur LiveKit (Cloud ou self-host) + identifiants d'API. Sans ces
        // trois valeurs, le fournisseur n'est pas disponible → repli sur Jitsi.
        'url' => env('LIVEKIT_URL'),          // wss://…
        'api_key' => env('LIVEKIT_API_KEY'),
        'api_secret' => env('LIVEKIT_API_SECRET'),
        'token_ttl' => (int) env('LIVEKIT_TOKEN_TTL', 7200),

        // App front qui affiche la salle (reçoit l'URL serveur + le token).
        // Par défaut l'app publique LiveKit Meet ; remplaçable par une version
        // self-host (qui, elle, peut être intégrée en iframe).
        'meet_url' => env('LIVEKIT_MEET_URL', 'https://meet.livekit.io/custom'),
    ],

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
