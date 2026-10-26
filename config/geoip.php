<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pays d'une adresse IP
    |--------------------------------------------------------------------------
    |
    | La base DB-IP Country Lite est embarquée dans le serveur : l'IP d'un
    | participant n'est transmise à personne. `php artisan geoip:update`
    | l'installe, et une tâche planifiée la rafraîchit chaque mois.
    |
    | ⚠️ Sans ce fichier, la colonne « Pays » reste vide. L'écran des
    | utilisateurs le dit maintenant à voix haute plutôt que d'afficher des
    | tirets ({@see \App\Support\IpCountry::databaseInstalled()}).
    |
    */

    'database_path' => env('GEOIP_DATABASE_PATH', storage_path('app/geoip/dbip-country-lite.mmdb')),

    /*
    |--------------------------------------------------------------------------
    | Repli hébergé
    |--------------------------------------------------------------------------
    |
    | Un service HTTP sans clé, consulté UNIQUEMENT pour les adresses que la
    | base locale n'a pas su lire. Le résultat est mis en cache.
    |
    | ⚠️⚠️ **IL TRANSMET L'ADRESSE DU VISITEUR À UN TIERS.** C'est pourquoi il
    | est éteint par défaut : allumer ce repli est une décision qui engage les
    | données des participants, pas un réglage de confort. `{ip}` est remplacé
    | par l'adresse à résoudre.
    |
    */

    'fallback' => [
        'enabled' => (bool) env('GEOIP_FALLBACK_ENABLED', false),
        'endpoint' => env('GEOIP_FALLBACK_ENDPOINT', 'https://ipapi.co/{ip}/json/'),
        'max_per_request' => (int) env('GEOIP_FALLBACK_MAX', 10),
        'timeout' => (int) env('GEOIP_FALLBACK_TIMEOUT', 3),
    ],

];
