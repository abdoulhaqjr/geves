<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Base fonctionnelle, TOUJOURS exécutée (y compris en production) :
        // rôles/permissions + compte administrateur.
        $this->call(RolesAndAdminSeeder::class);

        // Données de DÉMONSTRATION (faux colloques, participants, séances en direct).
        // Jamais en production, sauf override explicite SEED_DEMO_DATA=true (pour une
        // instance de démo/staging). Évite d'expédier des données fictives en prod.
        if (app()->isProduction() && ! env('SEED_DEMO_DATA', false)) {
            $this->command?->warn('Production : données de démonstration ignorées (SEED_DEMO_DATA=true pour forcer).');

            return;
        }

        $this->call([
            DemoColloqueSeeder::class,
            DemoScenarioSeeder::class,
            ColloqueShowcaseSeeder::class,
            ColloqueLiveSeeder::class,
        ]);
    }
}
