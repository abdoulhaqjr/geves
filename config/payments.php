<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passerelle de paiement par défaut
    |--------------------------------------------------------------------------
    |
    | Clé de la passerelle proposée en priorité au participant. Repli automatique
    | sur « manual » (paiement sur place / virement) si la passerelle choisie
    | n'est pas configurée. Passerelles : cashpay, fedapay, manual.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'manual'),

    'currency' => env('PAYMENT_CURRENCY', 'XOF'),

];
