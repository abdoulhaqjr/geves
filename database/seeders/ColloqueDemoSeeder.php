<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Jeu de données de DÉMONSTRATION des colloques : l'événement, ses ateliers, le
 * staff, les relecteurs, le direct (ColloqueAvenirSeeder) PUIS ses 200
 * communications et paiements (ColloqueAvenirVolumeSeeder).
 *
 * ⚠️ Ce groupe ne contient QUE des données de colloque et données connexes. Il
 * ne crée NI les rôles/permissions, NI le compte super-administrateur, NI les
 * réglages de la plateforme - cette base vit dans {@see RolesAndAdminSeeder} et
 * n'est jamais ré-amorcée par le réamorçage des colloques.
 *
 * C'est ce groupe que recrée la commande `php artisan colloques:reseed` (vide le
 * domaine « colloque » puis rejoue ces seeders), sans toucher à la base.
 */
class ColloqueDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ColloqueAvenirSeeder::class,
            ColloqueAvenirVolumeSeeder::class,
        ]);
    }
}
