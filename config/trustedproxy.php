<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mandataires de confiance
    |--------------------------------------------------------------------------
    |
    | En production, GEVES est servi derrière un mandataire (hébergement
    | mutualisé, Cloudflare). Sans cette liste, `$request->ip()` rend l'adresse
    | du MANDATAIRE : toutes les connexions s'enregistrent sous la même IP,
    | donc sous le même pays - une colonne « Pays » qui dit la même chose pour
    | tout le monde est pire qu'une colonne vide.
    |
    | ⚠️ **Le nom du fichier et celui de la clé ne se choisissent pas.**
    | `Illuminate\Http\Middleware\TrustProxies` - déjà dans la pile globale -
    | lit `config('trustedproxy.proxies')` À CHAQUE REQUÊTE quand aucun
    | mandataire ne lui a été fixé. C'est ce qui permet de régler ce point sans
    | y toucher dans `bootstrap/app.php`, où la configuration n'est pas encore
    | chargée au moment où la pile se compose. Un cas de garde éprouve le
    | branchement de bout en bout : si le framework le retirait, il tombe.
    |
    | ⚠️⚠️ **NE PAS METTRE `*` SANS MANDATAIRE DEVANT.** L'en-tête
    | `X-Forwarded-For` est écrit par le client : servi en direct, tout visiteur
    | choisirait l'adresse que GEVES enregistre sur sa connexion - la seule
    | trace dont on dispose pour dire d'où quelqu'un s'est connecté, et celle
    | dont se déduit le pays.
    |
    | ⚠️ **Vide par défaut** : c'est une décision de déploiement, pas un confort.
    | Valeurs : adresses ou blocs CIDR séparés par des virgules, ou `*` quand
    | l'hébergeur ne publie pas les siennes.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
