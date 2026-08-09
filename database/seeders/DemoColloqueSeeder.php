<?php

namespace Database\Seeders;

use App\Models\Colloque;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Colloque de démonstration avec une édition ouverte, pour illustrer la page
 * publique et le back-office.
 */
class DemoColloqueSeeder extends Seeder
{
    public function run(): void
    {
        $colloque = Colloque::firstOrCreate(
            ['slug' => 'colloque-international-uk'],
            [
                'name' => 'Colloque international de l’Université de Kara',
                'description' => 'Rendez-vous scientifique annuel de l’Université de Kara.',
            ]
        );

        $colloque->editions()->firstOrCreate(
            ['slug' => 'edition-2026'],
            [
                'title' => 'Colloque international de l’Université de Kara — 2026',
                'edition_label' => '3e édition',
                'subtitle' => 'Sciences, sociétés et développement durable en Afrique de l’Ouest',
                'theme' => 'Recherche et innovation au service du développement durable',
                'year' => 2026,
                'date_start' => Carbon::create(2026, 11, 18),
                'date_end' => Carbon::create(2026, 11, 20),
                'location' => 'Campus de l’Université de Kara, Togo',
                'status' => 'open',
                'submission_open' => true,
                'submission_deadline' => Carbon::create(2026, 9, 30, 23, 59),
                'settings' => [
                    'tracks' => [
                        'Sciences et technologies',
                        'Lettres, langues et sciences humaines',
                        'Sciences de la santé',
                        'Sciences économiques et de gestion',
                        'Agronomie et environnement',
                    ],
                    'intro' => 'La 3e édition du Colloque international de l’Université de Kara réunit '
                        . 'chercheurs, doctorants et praticiens autour des enjeux du développement durable. '
                        . 'Communications orales et posters sont attendus dans les cinq axes thématiques.',
                    'fees' => [
                        ['label' => 'Doctorant', 'amount' => 15000],
                        ['label' => 'Enseignant-chercheur', 'amount' => 25000],
                        ['label' => 'Professionnel', 'amount' => 35000],
                    ],
                ],
            ]
        );
    }
}
