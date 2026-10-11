<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // BASE fonctionnelle, TOUJOURS exécutée (y compris en production) :
        // rôles/permissions + compte super-administrateur. ⚠️ C'est la seule
        // partie que le réamorçage des colloques (`colloques:reseed`) NE touche
        // PAS - elle est volontairement séparée des données de démonstration.
        $this->call(RolesAndAdminSeeder::class);

        // Données de DÉMONSTRATION des colloques (événement, participants, séances
        // en direct, 200 communications…). Jamais en production, sauf override
        // explicite SEED_DEMO_DATA=true (pour une instance de démo/staging).
        // ⚠️ `config()`, pas `env()` : hors fichier de configuration, `env()`
        // rend `null` dès que la configuration est en cache, donc l'échappatoire
        // SEED_DEMO_DATA n'aurait jamais fonctionné en production.
        if (app()->isProduction() && ! config('super-admin.seed_demo_data')) {
            $this->command?->warn('Production : données de démonstration ignorées (SEED_DEMO_DATA=true pour forcer).');

            return;
        }

        // Un SEUL colloque de démonstration, au volume d'un vrai événement (le
        // VIIIe colloque conjoint Parakou-Kara). Regroupé dans ColloqueDemoSeeder
        // pour que `php artisan colloques:reseed` puisse le recréer seul.
        $this->call(ColloqueDemoSeeder::class);
    }
}
