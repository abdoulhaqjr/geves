<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Diffusion temps réel
    |--------------------------------------------------------------------------
    |
    | Les notifications instantanées utilisent le protocole Pusher, mais le serveur
    | est un service HÉBERGÉ (PieSocket, Pusher, Soketi…) désigné par son hôte :
    | la plateforme tourne sur un hébergement mutualisé, où l'on ne fait pas
    | tourner un démon WebSocket à soi.
    |
    | ⚠️ Les valeurs réelles viennent des RÉGLAGES (base), pas du .env :
    | `AppServiceProvider::applySettingsToConfig()` les surcharge au runtime,
    | comme le mail, les paiements et la visio. Le .env ne sert que de repli.
    |
    | ⚠️ `env()` ne s'appelle QUE depuis config/ : une fois `config:cache` passé,
    | il rend null partout ailleurs.
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                // ⚠️ Hôte VIDE = les serveurs de Pusher. Renseigné, il désigne
                // le service hébergé choisi (PieSocket…). Les deux cas doivent
                // marcher : c'est le même protocole.
                'host' => env('PUSHER_HOST') ?: null,
                'port' => (int) env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
