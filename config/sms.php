<?php

return [

    // Fournisseur par défaut : « log » (dev, journalise) ou « http » (API).
    // Surchargé au boot par le réglage back-office « sms.default ».
    'default' => env('SMS_PROVIDER', 'log'),

    // Durée de validité d'un code OTP (minutes).
    'otp_ttl' => env('SMS_OTP_TTL', 5),

    // Passerelle HTTP générique (identifiants renseignés en réglages / .env).
    'http' => [
        'url' => env('SMS_HTTP_URL'),
        'token' => env('SMS_HTTP_TOKEN'),
        'sender' => env('SMS_HTTP_SENDER', 'UKColloques'),
    ],

];
