<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Jeu de données de démonstration (persistant) pour peupler le back-office :
 * participants variés, communications réparties par statut / axe / mois, et
 * paiements. Idempotent par e-mail. À lancer : `php artisan db:seed --class=DemoDataSeeder`.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::where('status', 'open')->latest('id')->first() ?? Edition::latest('id')->first();
        if (! $edition) {
            $this->command?->warn('Aucune édition — créez-en une d’abord.');

            return;
        }

        $tracks = $edition->settings['tracks'] ?? ['Sciences et technologies', 'Sciences humaines et sociales', 'Santé et environnement'];
        $institutions = ['Université de Kara', 'Université de Lomé', 'Université de Parakou', 'Université d’Abomey-Calavi', 'Université Ouaga I'];
        $countries = ['Togo', 'Bénin', 'Burkina Faso', 'Côte d’Ivoire'];
        $titles = ['Enseignant-chercheur', 'Doctorant', 'Maître-assistant', 'Étudiant en master'];
        $people = [
            ['Awa', 'Kodjo'], ['Yao', 'Mensah'], ['Fatou', 'Diallo'], ['Kofi', 'Aganze'], ['Adjoa', 'Nkrumah'],
            ['Ibrahim', 'Traoré'], ['Mariam', 'Sawadogo'], ['Koffi', 'Adjovi'], ['Rita', 'Boni'], ['Serge', 'Kaboré'],
            ['Aminata', 'Bello'], ['Paul', 'Dossou'], ['Nadia', 'Ouédraogo'], ['Komla', 'Agbeko'],
        ];
        $statuses = ['submitted', 'submitted', 'under_review', 'under_review', 'accepted', 'accepted', 'accepted', 'revision', 'rejected'];
        $types = ['oral', 'oral', 'oral', 'poster'];

        foreach ($people as $i => [$first, $last]) {
            $email = Str::lower($first.'.'.$last.'@demo.uk').'';
            $participant = Participant::firstOrCreate(
                ['edition_id' => $edition->id, 'email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'institution' => $institutions[$i % count($institutions)],
                    'country' => $countries[$i % count($countries)],
                    'title' => $titles[$i % count($titles)],
                    'role' => 'author',
                    'checked_in_at' => $i % 4 === 0 ? now() : null,
                ]
            );

            // 1 à 2 communications par personne, réparties dans le temps.
            $count = $i % 3 === 0 ? 2 : 1;
            for ($k = 0; $k < $count; $k++) {
                $status = $statuses[($i + $k) % count($statuses)];
                $createdAt = now()->subDays(($i * 9 + $k * 20) % 170);

                $sub = Submission::firstOrCreate(
                    ['edition_id' => $edition->id, 'ref_code' => sprintf('%02d-%03d', $edition->year % 100, $i * 2 + $k + 1)],
                    [
                        'participant_id' => $participant->id,
                        'access_token' => Str::random(64),
                        'title' => 'Communication '.($i + 1).($k > 0 ? ' (bis)' : '').' — '.$tracks[($i + $k) % count($tracks)],
                        'authors' => [['name' => $first.' '.$last, 'email' => $email, 'corresponding' => true]],
                        'keywords' => 'recherche, '.Str::lower($tracks[($i + $k) % count($tracks)]),
                        'track' => $tracks[($i + $k) % count($tracks)],
                        'type' => $types[($i + $k) % count($types)],
                        'status' => $status,
                        'abstract' => '<p>Cette communication présente des travaux sur '.Str::lower($tracks[($i + $k) % count($tracks)])
                            .'. Contexte, objectifs, méthodologie et résultats attendus sont exposés dans le cadre de l’édition '.$edition->year.'.</p>',
                    ]
                );
                $sub->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
            }

            // Paiement pour ~2/3 des participants.
            if ($i % 3 !== 2) {
                Payment::firstOrCreate(
                    ['edition_id' => $edition->id, 'participant_id' => $participant->id],
                    [
                        'amount' => [15000, 25000, 30000][$i % 3],
                        'currency' => 'XOF',
                        'method' => ['mobile_money', 'card', 'manual'][$i % 3],
                        'gateway' => ['fedapay', 'cashpay', 'manual'][$i % 3],
                        'status' => $i % 5 === 0 ? 'pending' : 'paid',
                        'receipt_no' => 'RC-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                    ]
                );
            }
        }

        $this->command?->info('Démo : participants, communications et paiements créés pour « '.$edition->title.' ».');
    }
}
