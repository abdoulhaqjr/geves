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

    /*
    |--------------------------------------------------------------------------
    | Qualité vidéo de capture
    |--------------------------------------------------------------------------
    |
    | « sd » (640x360), « hd » (1280x720, défaut) ou « fhd » (1920x1080). C'est la
    | résolution de CAPTURE, donc la couche simulcast la plus haute : rien dans la
    | salle ne peut être plus net qu'elle. Les couches basses restent publiées pour
    | les vignettes et les connexions faibles. Réglable par événement (onglet
    | Visioconférence de la fiche d'événement) : le coût retombe sur la liaison
    | MONTANTE de celui qui diffuse.
    |
    */

    'quality' => env('MEETING_VIDEO_QUALITY', 'md'),

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

        // Enregistrement (Egress RoomComposite → fichier MP4). LiveKit dépose le
        // fichier dans un stockage objet compatible S3 fourni par l'université.
        // Sans bucket/région/identifiants, l'enregistrement reste indisponible.
        'egress' => [
            's3_bucket' => env('LIVEKIT_EGRESS_S3_BUCKET'),
            's3_region' => env('LIVEKIT_EGRESS_S3_REGION'),
            's3_access_key' => env('LIVEKIT_EGRESS_S3_ACCESS_KEY'),
            's3_secret' => env('LIVEKIT_EGRESS_S3_SECRET'),
            's3_endpoint' => env('LIVEKIT_EGRESS_S3_ENDPOINT'), // S3-compatible (MinIO…)
        ],
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
