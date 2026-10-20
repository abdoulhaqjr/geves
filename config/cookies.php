<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bandeau d'information sur les cookies
    |--------------------------------------------------------------------------
    |
    | Affiché sur les pages PUBLIQUES tant que le visiteur ne l'a pas écarté.
    | Surchargé au runtime depuis les Réglages (clé `cookies.notice_enabled`,
    | cf. AppServiceProvider::applySettingsToConfig) : c'est le réglage en base
    | qui décide, pas le `.env`.
    |
    */
    'notice_enabled' => env('COOKIE_NOTICE_ENABLED', true),

    /*
     * Cookie qui garde la trace de l'accusé de lecture. ⚠️ Il est PREMIÈRE
     * PARTIE et ne contient qu'un drapeau : il ne sert à aucun suivi.
     */
    'cookie_name' => 'geves_cookie_notice',

    /*
     * Durée de cet accusé de lecture, en jours. Au-delà, le bandeau reparaît -
     * ce qui est voulu : la liste des cookies a pu changer entre-temps.
     */
    'lifetime_days' => 180,

];
