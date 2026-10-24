<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passerelle de paiement
    |--------------------------------------------------------------------------
    |
    | Clé de la SEULE passerelle proposée au participant : choisir « fedapay »,
    | c'est n'encaisser qu'en ligne ; choisir « manual », c'est n'encaisser qu'au
    | guichet. Un événement peut la remplacer par la sienne
    | (App\Support\EventPaymentConfig). Repli automatique sur « manual » si la
    | passerelle choisie n'est pas configurée - une clé oubliée ne doit pas
    | fermer la caisse. Passerelles : fedapay, manual.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'manual'),

    'currency' => env('PAYMENT_CURRENCY', 'XOF'),

];
