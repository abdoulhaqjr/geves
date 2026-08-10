<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Passerelles de paiement (identifiants marchands ; vides = passerelle inactive).

    // CashPay (SEMOA) — OAuth 2.0 + facture hébergée (bill_url) + callback JWT HS256.
    'cashpay' => [
        'base_url' => env('CASHPAY_BASE_URL', 'https://api.semoa-payments.ovh/sandbox-v3'),
        'username' => env('CASHPAY_USERNAME'),
        'password' => env('CASHPAY_PASSWORD'),
        'client_id' => env('CASHPAY_CLIENT_ID', 'cashpay'),
        'client_secret' => env('CASHPAY_CLIENT_SECRET'),
        'api_key' => env('CASHPAY_API_KEY'), // clé de vérification du JWT de callback
    ],

    'fedapay' => [
        'secret' => env('FEDAPAY_SECRET'),
        'mode' => env('FEDAPAY_MODE', 'test'), // test (sandbox) | live
        'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
    ],

];
