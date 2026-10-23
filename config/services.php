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

    'turnstile' => [
        'site_key' => env('CLOUDFLARE_TURNSTILE_SITE_KEY'),
        'secret_key' => env('CLOUDFLARE_TURNSTILE_SECRET_KEY'),
    ],

    'docspace' => [
        'url' => env('DOCSPACE_URL'),
        'api_key' => env('DOCSPACE_API_KEY'),
        'room_id' => env('DOCSPACE_ROOM_ID'),
        'file_links_enabled' => env('DOCSPACE_FILE_LINKS_ENABLED', false),
    ],

    // Passerelles de paiement (identifiants marchands ; vides = passerelle inactive).

    'fedapay' => [
        'secret' => env('FEDAPAY_SECRET'),
        'mode' => env('FEDAPAY_MODE', 'test'), // test (sandbox) | live
        'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
        // Clé PUBLIQUE (pk_…), pour le formulaire embarqué dans notre page.
        // ⚠️ Elle part dans le navigateur, c'est son rôle - ne JAMAIS y mettre
        // la clé secrète. Sans elle, le paiement repart en redirection.
        'public_key' => env('FEDAPAY_PUBLIC_KEY'),
        // Proposer la CARTE BANCAIRE au payeur. ⚠️ C'est le compte marchand qui
        // décide vraiment : la carte demande une couverture (UEMOA ou monde) qui
        // s'accorde sur demande. Décocher ici si elle n'est pas ouverte, sinon on
        // annonce un moyen que la page du prestataire ne proposera pas.
        'card' => (bool) env('FEDAPAY_CARD', true),
    ],

    // Fournisseurs d'IA (paramétrage assisté, génération badges/attestations à venir).
    // model = modèle par défaut ; model_fast/model_deep = paliers « économie » et
    // « qualité » choisis automatiquement selon la tâche (cf. App\AI\AiRouter).
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL'),
        'model_fast' => env('OPENAI_MODEL_FAST'),
        'model_deep' => env('OPENAI_MODEL_DEEP'),
    ],
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL'),
        'model_fast' => env('ANTHROPIC_MODEL_FAST'),
        'model_deep' => env('ANTHROPIC_MODEL_DEEP'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL'),
        'model_fast' => env('GEMINI_MODEL_FAST'),
        'model_deep' => env('GEMINI_MODEL_DEEP'),
    ],
    'grok' => [
        'key' => env('GROK_API_KEY'),
        'model' => env('GROK_MODEL'),
        'model_fast' => env('GROK_MODEL_FAST'),
        'model_deep' => env('GROK_MODEL_DEEP'),
    ],
    'ai' => [
        'default' => env('AI_PROVIDER', 'claude'), // openai | claude | gemini | grok
        // Chatbot public (admins/gestionnaires non limités ; par IP si anonyme) :
        // rate-limit court terme (questions / heure) + plafond journalier (questions / jour).
        'public_rate_limit' => (int) env('AI_PUBLIC_RATE_LIMIT', 20),
        'public_daily_limit' => (int) env('AI_PUBLIC_DAILY_LIMIT', 30),
    ],

    // HAL / AuréHAL - autocomplétion des affiliations (référentiel des structures).
    // Désactivé par défaut ; activable dans les réglages (hal.enabled).
    'hal' => [
        'enabled' => (bool) env('HAL_ENABLED', false),
        'endpoint' => env('HAL_STRUCTURE_ENDPOINT', 'https://api.archives-ouvertes.fr/ref/structure/'),
    ],

    // Binaire Node.js utilisé pour fusionner les PDF du livret (pdf-lib).
    'livret_pdf_node_binary' => env('LIVRET_PDF_NODE_BINARY', 'node'),

];
