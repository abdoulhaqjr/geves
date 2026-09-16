<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Colloque;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SessionPoll;
use App\Models\SessionReport;
use App\Models\SessionQuestion;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Colloque de test « Innover pour un avenir durable » (VIIIe colloque conjoint
 * Parakou–Kara). Dates calées AUTOUR D'AUJOURD'HUI (colloque « en cours » de
 * 4 jours, hier → +2 j) pour tester le déroulement en direct : la 1re session
 * est marquée « live ». Crée l'édition, ses 6 ateliers et sessions, des
 * modérateurs et rapporteurs de test, et des communicants avec des
 * communications acceptées planifiées. Idempotent.
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
        // Dates autour d'aujourd'hui pour tester le « déroulement » (sessions en direct).
        $start = Carbon::now()->startOfDay()->subDay();   // hier
        $end = Carbon::now()->startOfDay()->addDays(2);   // dans 2 jours (colloque de 4 jours)

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
                'year' => (int) $start->year,
                'date_start' => $start->toDateString(),
                'date_end' => $end->toDateString(),
                'location' => 'Campus universitaire de Parakou (Bénin)',
                'status' => 'open',
                'submission_open' => true,
                'submission_deadline' => Carbon::now()->subMonths(3),
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
                        ['date' => Carbon::now()->subMonths(6)->toDateString(), 'label' => 'Lancement de l’appel'],
                        ['date' => Carbon::now()->subMonths(3)->toDateString(), 'label' => 'Date limite de réception des résumés'],
                        ['date' => Carbon::now()->subMonths(2)->toDateString(), 'label' => 'Notification d’acceptation'],
                        ['date' => $start->toDateString(), 'label' => 'Déroulement du colloque'],
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

        // Inscriptions (participants) des modérateurs / rapporteurs sur l'édition,
        // avec leur fonction (rôle) réelle pour piloter leurs espaces dédiés.
        foreach ($moderators as $u) {
            $this->participant($edition, $u, 'moderator', 'Enseignant-chercheur');
        }
        foreach ($rapporteurs as $u) {
            $this->participant($edition, $u, 'rapporteur', 'Enseignant-chercheur');
        }

        // Membre du secrétariat (staff) rattaché à l'édition : accès back-office
        // + poste de travail « Secrétariat » (validation des rapports de séance).
        $secretaire = $this->user('secretaire@test.tg', 'Sara', 'Secrétaire');
        $secretaire->assignedEditions()->syncWithoutDetaching([$edition->id => ['role' => 'secretariat']]);

        // Membre de la comptabilité (staff) : accès back-office + poste « Comptabilité ».
        $comptable = $this->user('comptable@test.tg', 'Carine', 'Comptable');
        $comptable->assignedEditions()->syncWithoutDetaching([$edition->id => ['role' => 'accounting']]);

        // --- Ateliers + sessions + communications acceptées et planifiées ---
        /** @var list<Submission> $submissions */
        $submissions = [];
        foreach (self::ATELIERS as $i => $title) {
            $atelier = Atelier::updateOrCreate(
                ['edition_id' => $edition->id, 'title' => $title],
                ['tracks' => [$title]]
            );

            $mod = $moderators[$i % 2];
            $rap = $rapporteurs[$i % 2];

            // La 1re session est EN DIRECT maintenant (test visio / Q&A) ; les
            // autres sont planifiées sur les 4 jours autour d'aujourd'hui.
            if ($i === 0) {
                $sStart = Carbon::now()->subMinutes(30);
                $sEnd = Carbon::now()->addMinutes(90);
                $meetingStatus = 'live';
            } else {
                $sStart = (clone $start)->addDays(intdiv($i, 2))->addHours(9 + ($i % 2) * 3);
                $sEnd = (clone $sStart)->addHours(2);
                $meetingStatus = 'scheduled';
            }

            $session = EventSession::updateOrCreate(
                ['edition_id' => $edition->id, 'label' => 'Session — '.$title],
                [
                    'atelier_id' => $atelier->id,
                    'type' => 'atelier',
                    'room' => 'Salle '.($i + 1),
                    'session_day' => $sStart->toDateString(),
                    'starts_at' => $sStart,
                    'ends_at' => $sEnd,
                    'meeting_status' => $meetingStatus,
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
            $submissions[] = $submission;

            Slot::updateOrCreate(
                ['session_id' => $session->id, 'submission_id' => $submission->id],
                [
                    'edition_id' => $edition->id,
                    'session_label' => $session->label,
                    'room' => $session->room,
                    'starts_at' => $sStart,
                    'ends_at' => (clone $sStart)->addMinutes(30),
                    'participant_id' => $participant->id,
                ]
            );

            // Paiement réglé (inscription confirmée) — sauf le dernier communicant,
            // laissé NON réglé pour tester les relances de paiement du secrétariat.
            if ($i === count(self::ATELIERS) - 1) {
                continue;
            }
            Payment::updateOrCreate(
                ['edition_id' => $edition->id, 'participant_id' => $participant->id],
                [
                    'amount' => 15000, 'currency' => 'XOF', 'method' => 'cash', 'gateway' => 'manual',
                    'status' => 'paid', 'paid_at' => Carbon::now()->subMonths(2),
                    'receipt_no' => sprintf('REC-%d-%03d', $start->year, $i + 1),
                ]
            );

            // Rapports de séance de démonstration : un soumis, un en brouillon,
            // pour tester la vue de consultation côté comité (back-office).
            if ($i === 1) {
                SessionReport::updateOrCreate(
                    ['session_id' => $session->id],
                    [
                        'edition_id' => $edition->id,
                        'reported_by' => $rap->id,
                        'summary' => 'La séance a réuni trois communications de qualité autour de l’innovation '
                            ."durable. Les échanges avec la salle ont été nourris, en particulier sur le financement\n"
                            .'de la recherche appliquée et les partenariats université–entreprise.',
                        'highlights' => 'Recommander la publication des actes ; encourager une session de suivi '
                            .'sur les mécanismes de valorisation.',
                        'attendance' => 58,
                        'status' => 'submitted',
                        'submitted_at' => Carbon::now()->subHours(3),
                    ]
                );
            } elseif ($i === 2) {
                SessionReport::updateOrCreate(
                    ['session_id' => $session->id],
                    [
                        'edition_id' => $edition->id,
                        'reported_by' => $rap->id,
                        'summary' => 'Brouillon en cours de rédaction…',
                        'status' => 'draft',
                    ]
                );
            }

            // Matière pour la session EN DIRECT : questions Q&A + un sondage ouvert.
            if ($i === 0) {
                foreach ([
                    'Comment mesurer concrètement l’impact des universités sur les ODD ?',
                    'Quels leviers de financement pour l’innovation à fort impact ?',
                    'Comment articuler savoirs endogènes et recherche globale ?',
                ] as $n => $q) {
                    SessionQuestion::updateOrCreate(
                        ['session_id' => $session->id, 'body' => $q],
                        ['edition_id' => $edition->id, 'author_name' => 'Auditeur '.($n + 1), 'status' => 'pending']
                    );
                }
                SessionPoll::updateOrCreate(
                    ['session_id' => $session->id, 'question' => 'Quel est le principal frein à l’innovation universitaire ?'],
                    [
                        'edition_id' => $edition->id, 'status' => 'open',
                        'options' => ['Financement', 'Gouvernance', 'Partenariats', 'Valorisation'],
                    ]
                );
            }
        }

        // --- Relecteurs de test + relectures ---
        $reviewers = [
            $this->reviewer($edition, 'relecteur1@test.tg', 'Rebecca', 'Relectrice'),
            $this->reviewer($edition, 'relecteur2@test.tg', 'Robert', 'Relecteur'),
        ];

        // Les communications ACCEPTÉES (déjà planifiées au programme) ont été
        // relues et évaluées : chacune reçoit une relecture TERMINÉE. Le statut
        // « accepté » n'est pas modifié (cohérent avec leur présence au programme).
        foreach ($submissions as $i => $submission) {
            $review = Review::updateOrCreate(
                ['submission_id' => $submission->id, 'reviewer_id' => $reviewers[$i % 2]->id],
                ['status' => 'completed']
            );
            $review->update([
                'recommendation' => $i === 0 ? 'minor' : 'accept',
                'score' => 15 + ($i % 4),
                'scores' => ['originalite' => 4, 'methode' => 4, 'clarte' => 4 + ($i % 2), 'pertinence' => 4],
                'comments' => 'Communication solide et bien structurée. Références actualisées ; '
                    .'protocole clair et résultats convaincants.',
                'comments_private' => 'Aucun conflit d’intérêts. Favorable à l’acceptation.',
            ]);
        }

        // --- Pipeline d'évaluation EN COURS (soumissions non planifiées) ---
        // Jeu de communications encore dans le circuit d'évaluation, pour tester
        // le portail relecteur (« à faire »), le workflow de décision et la
        // phase de réponse aux relecteurs (rebuttal).
        $pending = [
            // [ref, statut, état relecture, options]
            ['UK-2025-101', 'submitted', 'assigned', []],
            ['UK-2025-102', 'under_review', 'completed', ['rebuttal' => true]],
            ['UK-2025-103', 'revision', 'completed', []],
        ];
        foreach ($pending as $k => [$ref, $status, $reviewState, $opts]) {
            $author = $this->user('auteur'.($k + 1).'@test.tg', 'Auteur'.($k + 1), 'Évaluation');
            $participant = $this->participant($edition, $author, 'author', 'Doctorant');
            $track = self::ATELIERS[$k % count(self::ATELIERS)];

            $submission = Submission::updateOrCreate(
                ['edition_id' => $edition->id, 'ref_code' => $ref],
                [
                    'participant_id' => $participant->id,
                    'access_token' => Str::random(40),
                    'title' => 'Communication en évaluation '.($k + 1),
                    'authors' => [['name' => $author->name, 'email' => $author->email, 'corresponding' => true]],
                    'abstract' => 'Résumé d’une communication encore en cours d’évaluation, pour tester le circuit de relecture.',
                    'keywords' => 'innovation, durabilité, universités',
                    'track' => $track,
                    'type' => 'oral',
                    'status' => $status,
                    'revision_of' => 0,
                ]
            );

            $review = Review::updateOrCreate(
                ['submission_id' => $submission->id, 'reviewer_id' => $reviewers[$k % 2]->id],
                ['status' => $reviewState]
            );
            if ($reviewState === 'completed') {
                $review->update([
                    'recommendation' => $status === 'revision' ? 'major' : 'minor',
                    'score' => 12 + $k,
                    'scores' => ['originalite' => 3, 'methode' => 3, 'clarte' => 3, 'pertinence' => 4],
                    'comments' => 'Contribution intéressante mais des révisions sont nécessaires : '
                        .'préciser la méthodologie et étayer la discussion des résultats.',
                    'comments_private' => 'À reconsidérer après réponse de l’auteur.',
                ]);
            }

            // Phase de réponse aux relecteurs ouverte (auteur invité à répondre).
            if (! empty($opts['rebuttal'])) {
                $meta = (array) $submission->meta;
                $meta['rebuttal'] = ['opened_at' => Carbon::now()->subDays(2)->toIso8601String(), 'text' => null];
                $submission->update(['meta' => $meta]);
            }
        }

        $this->command?->info('Colloque de test « Avenir durable 2025 » créé (édition open, 6 ateliers).');
        $this->command?->info('Modérateurs : moderateur1@test.tg · moderateur2@test.tg');
        $this->command?->info('Rapporteurs : rapporteur1@test.tg · rapporteur2@test.tg');
        $this->command?->info('Secrétariat (back-office) : secretaire@test.tg — mot de passe : password');
        $this->command?->info('Comptabilité (back-office) : comptable@test.tg — mot de passe : password');
        $this->command?->info('Communicants : comm1@test.tg … comm6@test.tg — mot de passe : password');
        $this->command?->info('Auteurs en évaluation : auteur1@test.tg (soumise) · auteur2@test.tg (réponse ouverte) · auteur3@test.tg (correction demandée)');
        foreach ($reviewers as $r) {
            $this->command?->info('Relecteur : '.$r->email.' — mot de passe : password (relectures dans « Mes relectures »)');
        }
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

    /**
     * Relecteur de test : compte (login) + inscription « reviewer » sur l'édition
     * avec jeton d'accès au portail de relecture. Garantit un jeton stable.
     */
    private function reviewer(Edition $edition, string $email, string $first, string $last): Participant
    {
        $user = $this->user($email, $first, $last);
        $participant = $this->participant($edition, $user, 'reviewer', 'Enseignant-chercheur');

        if (blank($participant->access_token)) {
            $participant->access_token = Str::random(64);
            $participant->save();
        }

        return $participant;
    }
}
