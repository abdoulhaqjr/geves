<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Colloque de test « Innover pour un avenir durable » (VIIIe colloque conjoint
 * Parakou–Kara), tenu du 15 au 18 septembre 2025. Crée l'édition, ses 6 ateliers
 * et sessions, des modérateurs et rapporteurs de test, et des communicants avec
 * des communications acceptées planifiées. Idempotent.
 *
 *   php artisan db:seed --class=ColloqueAvenirSeeder
 *
 * Tous les mots de passe : « password ».
 */
class ColloqueAvenirSeeder extends Seeder
{
    private const ATELIERS = [
        'Atelier 1 — Sciences médicales, sanitaires, sportives et innovations médicinales',
        'Atelier 2 — Sciences naturelles, agronomiques et biologiques',
        'Atelier 3 — Langues, lettres, sciences sociales et humaines',
        'Atelier 4 — Sciences juridiques, politiques, administratives et innovations',
        'Atelier 5 — Sciences économiques, de gestion et entreprenariat',
        'Atelier 6 — Mathématiques, physique, chimie et innovations technologiques',
    ];

    public function run(): void
    {
        $colloque = Colloque::updateOrCreate(
            ['slug' => 'parakou-kara'],
            ['name' => 'Colloque scientifique international conjoint Parakou–Kara']
        );

        $edition = $colloque->editions()->updateOrCreate(
            ['slug' => 'avenir-durable-2025'],
            [
                'title' => 'VIIIe Colloque — Innover pour un avenir durable',
                'edition_label' => 'VIIIe édition',
                'subtitle' => 'Enjeux, défis et rôles des universités africaines',
                'theme' => 'Innover pour un avenir durable : enjeux, défis et rôles des universités africaines',
                'year' => 2025,
                'date_start' => '2025-09-15',
                'date_end' => '2025-09-18',
                'location' => 'Campus universitaire de Parakou (Bénin)',
                'status' => 'open',
                'submission_open' => true,
                'submission_deadline' => '2025-06-15 23:59:00',
                'settings' => [
                    'intro' => 'Le colloque « Universités africaines et développement : innover pour un avenir durable » '
                        .'offre un cadre de réflexion, de partage d’expériences et de dialogue entre universitaires, chercheurs, '
                        .'étudiants, décideurs publics, acteurs du secteur privé et partenaires techniques et financiers.',
                    'short_name' => 'Colloque Avenir durable 2025',
                    'main_language' => 'fr',
                    'languages' => ['Français', 'Anglais'],
                    'format' => 'Présentiel',
                    'tracks' => self::ATELIERS,
                    'fees' => [
                        ['label' => 'Enseignants et chercheurs', 'amount' => 35000],
                        ['label' => 'Doctorants et assimilés', 'amount' => 15000],
                        ['label' => 'Autres participants', 'amount' => 50000],
                    ],
                    'max_communications' => 3,
                    'coauthors_max' => 8,
                    'attestation_max' => 5,
                    'key_dates' => [
                        ['date' => '2026-01-29', 'label' => 'Lancement de l’appel'],
                        ['date' => '2025-06-15', 'label' => 'Date limite de réception des résumés'],
                        ['date' => '2025-06-30', 'label' => 'Notification d’acceptation'],
                        ['date' => '2025-09-15', 'label' => 'Déroulement du colloque'],
                    ],
                    'contact' => [
                        'email' => 'colloque@annales-up.com',
                        'phone' => '(+229) 0197930087',
                        'address' => 'Campus universitaire de Parakou, Bénin',
                    ],
                ],
            ]
        );

        // --- Comptes de test : modérateurs, rapporteurs, communicants ---
        $moderators = [
            $this->user('moderateur1@test.tg', 'Modeste', 'Modérateur'),
            $this->user('moderateur2@test.tg', 'Mariam', 'Modératrice'),
        ];
        $rapporteurs = [
            $this->user('rapporteur1@test.tg', 'Rachid', 'Rapporteur'),
            $this->user('rapporteur2@test.tg', 'Rita', 'Rapporteuse'),
        ];

        // Inscriptions (participants) des modérateurs / rapporteurs sur l'édition.
        foreach (array_merge($moderators, $rapporteurs) as $u) {
            $this->participant($edition, $u, 'attendee', 'Enseignant-chercheur');
        }

        // --- Ateliers + sessions + communications acceptées et planifiées ---
        $day = Carbon::parse('2025-09-15 09:00:00');

        foreach (self::ATELIERS as $i => $title) {
            $atelier = Atelier::updateOrCreate(
                ['edition_id' => $edition->id, 'title' => $title],
                ['tracks' => [$title]]
            );

            $mod = $moderators[$i % 2];
            $rap = $rapporteurs[$i % 2];
            $start = (clone $day)->addDays(intdiv($i, 2))->addHours(($i % 2) * 3);

            $session = EventSession::updateOrCreate(
                ['edition_id' => $edition->id, 'label' => 'Session — '.$title],
                [
                    'atelier_id' => $atelier->id,
                    'type' => 'atelier',
                    'room' => 'Salle '.($i + 1),
                    'session_day' => $start->toDateString(),
                    'starts_at' => $start,
                    'ends_at' => (clone $start)->addHours(2),
                    'moderator_name' => $mod->name,
                    'moderator_email' => $mod->email,
                    'rapporteur_name' => $rap->name,
                    'rapporteur_email' => $rap->email,
                ]
            );

            // Communicant + communication acceptée, planifiée dans la session.
            $comm = $this->user('comm'.($i + 1).'@test.tg', 'Communicant'.($i + 1), 'Test');
            $participant = $this->participant($edition, $comm, 'author', 'Doctorant');

            $submission = Submission::updateOrCreate(
                ['edition_id' => $edition->id, 'ref_code' => sprintf('UK-2025-%03d', $i + 1)],
                [
                    'participant_id' => $participant->id,
                    'access_token' => Str::random(40),
                    'title' => 'Communication test '.($i + 1).' — '.Str::before(Str::after($title, '— '), ','),
                    'authors' => [['name' => $comm->name, 'email' => $comm->email, 'corresponding' => true]],
                    'abstract' => 'Résumé de démonstration pour tester le déroulement du colloque, la modération et le rapportage.',
                    'keywords' => 'innovation, durabilité, universités',
                    'track' => $title,
                    'type' => 'oral',
                    'status' => 'accepted',
                    'revision_of' => 0,
                ]
            );

            Slot::updateOrCreate(
                ['session_id' => $session->id, 'submission_id' => $submission->id],
                [
                    'edition_id' => $edition->id,
                    'session_label' => $session->label,
                    'room' => $session->room,
                    'starts_at' => $start,
                    'ends_at' => (clone $start)->addMinutes(30),
                    'participant_id' => $participant->id,
                ]
            );
        }

        $this->command?->info('Colloque de test « Avenir durable 2025 » créé (édition open, 6 ateliers).');
        $this->command?->info('Modérateurs : moderateur1@test.tg · moderateur2@test.tg');
        $this->command?->info('Rapporteurs : rapporteur1@test.tg · rapporteur2@test.tg');
        $this->command?->info('Communicants : comm1@test.tg … comm6@test.tg — mot de passe : password');
    }

    private function user(string $email, string $first, string $last): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => trim($first.' '.$last),
                'first_name' => $first,
                'last_name' => $last,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'access_level' => User::ACCESS_USER,
                'affiliation' => 'Université de Kara',
            ]
        );

        return $user;
    }

    private function participant(Edition $edition, User $user, string $role, string $title): Participant
    {
        return Participant::updateOrCreate(
            ['edition_id' => $edition->id, 'user_id' => $user->id],
            [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'title' => $title,
                'role' => $role,
                'institution' => 'Université de Kara',
                'country' => 'Togo',
            ]
        );
    }
}
