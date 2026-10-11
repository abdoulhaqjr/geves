<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Compte super-administrateur installé par le seeder
    |--------------------------------------------------------------------------
    |
    | `RolesAndAdminSeeder` tourne AUSSI en production : c'est lui qui pose les
    | rôles, les permissions et le compte à tous les pouvoirs. Ces valeurs sont
    | lues UNE SEULE FOIS, à la création du compte. Les exécutions suivantes ne
    | retouchent jamais un mot de passe déjà changé depuis l'interface.
    |
    | ⚠️ Ces clés vivent ICI, et pas en `env()` dans le seeder, parce que
    | `env()` rend `null` dès que la configuration est en cache
    | (`php artisan config:cache`) : en production, où la mise en cache est la
    | règle, un seeder qui lit l'environnement directement ne voit RIEN. Seuls
    | les fichiers de configuration ont le droit d'appeler `env()`.
    |
    | ⚠️ Après toute modification du `.env`, relancer `php artisan config:cache`
    | (ou `config:clear`) : sinon l'ancienne valeur en cache reste la seule lue.
    |
    */

    'name' => env('ADMIN_NAME', 'Administrateur'),

    'email' => env('ADMIN_EMAIL', 'admin@univkara.tg'),

    /*
    | Vide en production = le seeder ARRÊTE le déploiement plutôt que de créer
    | un compte à tous les pouvoirs avec un mot de passe connu de tous.
    */
    'password' => env('ADMIN_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Données de démonstration
    |--------------------------------------------------------------------------
    |
    | `DatabaseSeeder` ignore les colloques de démonstration en production.
    | Mettre SEED_DEMO_DATA=true pour une instance de démonstration ou de
    | pré-production, qui a besoin d'un jeu de données réaliste.
    |
    */

    'seed_demo_data' => (bool) env('SEED_DEMO_DATA', false),

];
