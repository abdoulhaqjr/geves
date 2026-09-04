<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Base fonctionnelle : rôles/permissions + administrateur + édition « à venir ».
        // Puis démo complète reproductible : 2 colloques passés (cycle complet),
        // une édition « en cours », l'équipe back-office et un compte participant.
        // Enfin, un colloque « en déroulement » (dates encadrant aujourd'hui) avec
        // une séance active chaque jour, pour la page /deroulement.
        // ⚠️ En production, ne pas seeder les données de démonstration.
        $this->call([
            RolesAndAdminSeeder::class,
            DemoColloqueSeeder::class,
            DemoScenarioSeeder::class,
            ColloqueShowcaseSeeder::class,
            ColloqueLiveSeeder::class,
        ]);
    }
}
