<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Claim;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SessionPoll;
use App\Models\SessionQuestion;
use App\Models\SessionReport;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Colloque de test « Innover pour un avenir durable » (VIIIe colloque conjoint
 * Parakou-Kara). Dates calées AUTOUR D'AUJOURD'HUI (colloque « en cours » de
 * 4 jours, hier → +2 j) pour tester le déroulement en direct : la 1re session
 * est marquée « live ». Crée l'événement, ses 6 ateliers et sessions, des
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
        'Atelier 1 - Sciences médicales, sanitaires, sportives et innovations médicinales',
        'Atelier 2 - Sciences naturelles, agronomiques et biologiques',
        'Atelier 3 - Langues, lettres, sciences sociales et humaines',
        'Atelier 4 - Sciences juridiques, politiques, administratives et innovations',
        'Atelier 5 - Sciences économiques, de gestion et entreprenariat',
        'Atelier 6 - Mathématiques, physique, chimie et innovations technologiques',
    ];

    public function run(): void
    {
        // Dates FIXES de l'événement : le colloque se tient du 2 au 8 octobre 2026
        // (vendredi → jeudi). Il reste « en cours » tant qu'on est dans cette
        // fenêtre, ce qui alimente la page « déroulement » et les sessions en direct.
        //
        // ⚠️ Tout le reste se CALE SUR `$start` par décalage en jours (plénières,
        // programme synthétique, et jusqu'aux séances du seeder de volume, qui
        // relit `date_start`). Déplacer le colloque = changer ces deux lignes, pas
        // des dates écrites en dur ailleurs. Les décalages vont de J+0 à J+6 :
        // tenir la fenêtre à 7 jours, sinon la clôture sortirait de l'événement.
        $start = Carbon::create(2026, 10, 2)->startOfDay();
        $end = Carbon::create(2026, 10, 8)->startOfDay();
        $deadline = Carbon::now()->subMonths(3); // clôture des soumissions (passée)

        $series = EventSeries::updateOrCreate(
            ['slug' => 'parakou-kara'],
            ['name' => 'Colloque scientifique international conjoint Parakou-Kara']
        );

        $event = $series->events()->updateOrCreate(
            ['slug' => 'avenir-durable-2025'],
            [
                'title' => 'VIIIe Colloque - Innover pour un avenir durable',
                'event_label' => 'VIIIe événement',
                'subtitle' => 'Enjeux, défis et rôles des universités africaines',
                'theme' => 'Innover pour un avenir durable : enjeux, défis et rôles des universités africaines',
                'year' => (int) $start->year,
                'date_start' => $start->toDateString(),
                'date_end' => $end->toDateString(),
                'location' => 'Campus universitaire de Parakou (Bénin)',
                'status' => 'open',
                // Ouvertes seulement si la clôture n'est pas passée : évite une
                // incohérence « soumissions ouvertes » pendant le déroulement.
                'submission_open' => $deadline->isFuture(),
                'submission_deadline' => $deadline,
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
                    // Programme synthétique (onglet « Programme ») publié tel quel sur la
                    // page du colloque (onglets jour par jour) et source de génération.
                    'program_synthesis' => [
                        'title' => 'Programme synthétique du colloque',
                        'days' => [
                            ['date' => $start->toDateString(), 'heading' => 'Matin', 'rows' => [
                                ['type' => 'other', 'start' => '08:00', 'end' => '09:00', 'label' => 'Arrivée, distribution des badges et installation des participants', 'highlight' => false],
                                ['type' => 'plenary', 'start' => '09:00', 'end' => '10:30', 'label' => 'Séance inaugurale', 'highlight' => true],
                                ['type' => 'break', 'start' => '10:30', 'end' => '11:00', 'label' => 'Pause-café', 'highlight' => false],
                            ]],
                            ['date' => $start->toDateString(), 'heading' => 'Début des ateliers', 'rows' => [
                                ['type' => 'atelier', 'start' => '11:30', 'end' => '12:30', 'label' => 'Communications en ateliers', 'highlight' => false],
                                ['type' => 'break', 'start' => '12:30', 'end' => '14:00', 'label' => 'Pause déjeuner', 'highlight' => false],
                                ['type' => 'atelier', 'start' => '14:00', 'end' => '17:00', 'label' => 'Communications en ateliers', 'highlight' => false],
                            ]],
                            ['date' => (clone $start)->addDays(1)->toDateString(), 'heading' => 'Journée d’ateliers', 'rows' => [
                                ['type' => 'atelier', 'start' => '08:30', 'end' => '11:30', 'label' => 'Communications en ateliers', 'highlight' => false],
                                ['type' => 'break', 'start' => '12:30', 'end' => '14:00', 'label' => 'Pause déjeuner', 'highlight' => false],
                                ['type' => 'atelier', 'start' => '14:00', 'end' => '17:00', 'label' => 'Communications en ateliers (dont séances à distance)', 'highlight' => false],
                            ]],
                            ['date' => (clone $start)->addDays(2)->toDateString(), 'heading' => 'Matin', 'rows' => [
                                ['type' => 'atelier', 'start' => '08:30', 'end' => '11:30', 'label' => 'Communications en ateliers', 'highlight' => false],
                            ]],
                            ['date' => (clone $start)->addDays(3)->toDateString(), 'heading' => 'Matin', 'rows' => [
                                ['type' => 'plenary', 'start' => '08:00', 'end' => '10:00', 'label' => 'Table ronde (diffusée en ligne)', 'highlight' => true],
                                ['type' => 'break', 'start' => '10:00', 'end' => '10:30', 'label' => 'Pause-café', 'highlight' => false],
                                ['type' => 'atelier', 'start' => '14:00', 'end' => '17:00', 'label' => 'Communications en ateliers', 'highlight' => false],
                            ]],
                            ['date' => (clone $start)->addDays(4)->toDateString(), 'heading' => 'Après-midi', 'rows' => [
                                ['type' => 'other', 'start' => '14:00', 'end' => '17:00', 'label' => 'Formation', 'highlight' => true],
                            ]],
                            ['date' => (clone $start)->addDays(5)->toDateString(), 'heading' => 'Matin', 'rows' => [
                                ['type' => 'atelier', 'start' => '08:30', 'end' => '11:30', 'label' => 'Communications en ateliers', 'highlight' => false],
                            ]],
                            ['date' => (clone $start)->addDays(6)->toDateString(), 'heading' => 'Clôture', 'rows' => [
                                ['type' => 'plenary', 'start' => '11:00', 'end' => '12:30', 'label' => 'Cérémonie de clôture', 'highlight' => true],
                            ]],
                        ],
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

        // Inscriptions (participants) des modérateurs / rapporteurs sur l'événement,
        // avec leur fonction (rôle) réelle pour piloter leurs espaces dédiés.
        foreach ($moderators as $u) {
            $this->participant($event, $u, 'moderator', 'Enseignant / Chercheur');
        }
        foreach ($rapporteurs as $u) {
            $this->participant($event, $u, 'rapporteur', 'Enseignant / Chercheur');
        }

        // Membre du secrétariat (staff) rattaché à l'événement : accès back-office
        // + poste de travail « Secrétariat » (validation des rapports de séance).
        $secretaire = $this->user('secretaire@test.tg', 'Sara', 'Secrétaire');
        $this->assignStaff($secretaire, $event, 'secretariat');

        // Membre de la comptabilité (staff) : accès back-office + poste « Comptabilité ».
        $comptable = $this->user('comptable@test.tg', 'Carine', 'Comptable');
        $this->assignStaff($comptable, $event, 'accounting');

        // Sessions plénières (EXCLUSIVES) : inauguration, table ronde, formation,
        // clôture. Calées hors des fenêtres des ateliers pour respecter l'exclusivité.
        $this->buildPlenaries($event, $moderators, $start);

        // --- Ateliers + sessions + communications acceptées et planifiées ---
        /** @var list<Submission> $submissions */
        $submissions = [];
        foreach (self::ATELIERS as $i => $title) {
            $atelier = Atelier::updateOrCreate(
                ['event_id' => $event->id, 'title' => $title],
                ['tracks' => [$title]]
            );

            $mod = $moderators[$i % 2];
            $rap = $rapporteurs[$i % 2];

            // Les trois états d'une visio sont représentés, pour qu'on puisse les
            // voir sans attendre : la 1re séance est EN DIRECT, la 2e est TERMINÉE
            // avec un replay, les autres sont PROGRAMMÉES.
            $recording = null;
            if ($i === 0) {
                // Séance en direct dans un amphi dédié : n'entre pas en conflit de
                // salle avec les ateliers de volume (qui occupent « Salle 1 » à
                // « Salle 6 »), quelle que soit l'heure courante.
                $sStart = Carbon::now()->subMinutes(30);
                $sEnd = Carbon::now()->addMinutes(90);
                $meetingStatus = 'live';
                $room = 'Auditorium';
            } elseif ($i === 1) {
                // ⚠️ Calée sur l'HORLOGE, comme la séance en direct : une séance
                // « terminée » posée à une date fixe se retrouverait annoncée
                // terminée alors qu'elle n'a pas eu lieu.
                $sStart = Carbon::now()->subHours(3);
                $sEnd = Carbon::now()->subMinutes(90);
                $meetingStatus = 'ended';
                $room = 'Auditorium B';
                $recording = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';
            } else {
                // Créneau 11:30-12:30, laissé libre par les ateliers de volume (matin
                // 08:30-11:30, après-midi 14:00-17:00), AVANT la pause déjeuner
                // (12:30-14:00, exclusive) et hors des fenêtres des plénières :
                // aucun conflit de salle, de plénière ni de pause.
                $day = (clone $start)->addDays(intdiv($i, 2));
                $sStart = (clone $day)->setTime(11, 30);
                $sEnd = (clone $day)->setTime(12, 30);
                $meetingStatus = 'scheduled';
                $room = 'Salle '.($i + 1);
            }

            $session = EventSession::updateOrCreate(
                ['event_id' => $event->id, 'label' => 'Session - '.$title],
                [
                    'atelier_id' => $atelier->id,
                    'type' => 'atelier',
                    'room' => $room,
                    'session_day' => $sStart->toDateString(),
                    'starts_at' => $sStart,
                    'ends_at' => $sEnd,
                    // ⚠️ Fournisseur NON forcé : il suit `meetings.default`. Le
                    // registre retombe seul sur Jitsi public si le défaut n'est pas
                    // configuré - forcer ici écraserait la configuration du site.
                    'meeting_provider' => null,
                    'meeting_status' => $meetingStatus,
                    'meeting_visibility' => 'participants',
                    'recording_url' => $recording,
                    'moderator_name' => $mod->name,
                    'moderator_email' => $mod->email,
                    'rapporteur_name' => $rap->name,
                    'rapporteur_email' => $rap->email,
                ]
            );

            // Communicant + communication acceptée, planifiée dans la session.
            $comm = $this->user('comm'.($i + 1).'@test.tg', 'Communicant'.($i + 1), 'Test');
            $participant = $this->participant($event, $comm, 'author', 'Doctorant');

            $submission = Submission::updateOrCreate(
                ['event_id' => $event->id, 'ref_code' => sprintf('UK-2025-%03d', $i + 1)],
                [
                    'participant_id' => $participant->id,
                    'access_token' => Str::random(40),
                    'title' => 'Communication test '.($i + 1).' - '.Str::before(Str::after($title, '- '), ','),
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
                    'event_id' => $event->id,
                    'session_label' => $session->label,
                    'room' => $session->room,
                    'starts_at' => $sStart,
                    'ends_at' => (clone $sStart)->addMinutes(30),
                    'participant_id' => $participant->id,
                ]
            );

            // Paiement réglé (inscription confirmée) - sauf le dernier communicant,
            // laissé NON réglé pour tester les relances de paiement (page Paiements).
            if ($i === count(self::ATELIERS) - 1) {
                continue;
            }
            Payment::updateOrCreate(
                ['event_id' => $event->id, 'participant_id' => $participant->id],
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
                        'event_id' => $event->id,
                        'reported_by' => $rap->id,
                        'summary' => 'La séance a réuni trois communications de qualité autour de l’innovation '
                            ."durable. Les échanges avec la salle ont été nourris, en particulier sur le financement\n"
                            .'de la recherche appliquée et les partenariats université-entreprise.',
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
                        'event_id' => $event->id,
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
                        ['event_id' => $event->id, 'author_name' => 'Auditeur '.($n + 1), 'status' => 'pending']
                    );
                }
                SessionPoll::updateOrCreate(
                    ['session_id' => $session->id, 'question' => 'Quel est le principal frein à l’innovation universitaire ?'],
                    [
                        'event_id' => $event->id, 'status' => 'open',
                        'options' => ['Financement', 'Gouvernance', 'Partenariats', 'Valorisation'],
                    ]
                );
            }
        }

        // --- Relecteurs de test + relectures ---
        $reviewers = [
            $this->reviewer($event, 'relecteur1@test.tg', 'Rebecca', 'Relectrice'),
            $this->reviewer($event, 'relecteur2@test.tg', 'Robert', 'Relecteur'),
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
            $participant = $this->participant($event, $author, 'author', 'Doctorant');
            $track = self::ATELIERS[$k % count(self::ATELIERS)];

            $submission = Submission::updateOrCreate(
                ['event_id' => $event->id, 'ref_code' => $ref],
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

        // --- Réclamations / incidents de démonstration (comme via l'assistante Gesco) ---
        $comm1 = User::whereRaw('LOWER(email) = ?', ['comm1@test.tg'])->first();
        Claim::updateOrCreate(
            ['reference' => 'REC-'.$start->year.'-DEMO01'],
            [
                'event_id' => $event->id, 'user_id' => $comm1?->id,
                'name' => $comm1?->name ?: 'Communicant1 Test', 'email' => 'comm1@test.tg',
                'type' => 'reclamation', 'category' => 'paiement',
                'subject' => 'Paiement effectué mais inscription encore « à régler »',
                'message' => "J'ai réglé mes frais via Mobile Money, mais mon espace indique toujours « à régler ». "
                    .'Merci de vérifier et de régulariser mon inscription.',
                'status' => 'open', 'source' => 'chatbot',
            ]
        );
        Claim::updateOrCreate(
            ['reference' => 'INC-'.$start->year.'-DEMO02'],
            [
                'event_id' => $event->id,
                'name' => 'Auditeur externe', 'email' => 'auditeur@example.tg',
                'type' => 'incident', 'category' => 'technique',
                'subject' => 'Impossible de rejoindre la visio de la séance en direct',
                'message' => 'Le bouton « Rejoindre la visio » ne réagit pas sur mon navigateur pendant la séance en direct.',
                'status' => 'in_progress', 'source' => 'chatbot',
            ]
        );

        $this->command?->info('Colloque de test « Avenir durable 2025 » créé (événement open, 6 ateliers).');
        $this->command?->info('Modérateurs : moderateur1@test.tg · moderateur2@test.tg');
        $this->command?->info('Rapporteurs : rapporteur1@test.tg · rapporteur2@test.tg');
        $this->command?->info('Secrétariat (back-office) : secretaire@test.tg - mot de passe : password');
        $this->command?->info('Comptabilité (back-office) : comptable@test.tg - mot de passe : password');
        $this->command?->info('Communicants : comm1@test.tg … comm6@test.tg - mot de passe : password');
        $this->command?->info('Auteurs en évaluation : auteur1@test.tg (soumise) · auteur2@test.tg (réponse ouverte) · auteur3@test.tg (correction demandée)');
        foreach ($reviewers as $r) {
            $this->command?->info('Relecteur : '.$r->email.' - mot de passe : password (relectures dans « Mes relectures »)');
        }
    }

    /**
     * Sessions plénières (exclusives) de l'événement, dans le « Grand amphithéâtre ».
     * Placées hors des fenêtres des ateliers (matin 08:30-11:30, après-midi
     * 14:00-17:00) pour que l'exclusivité des plénières soit respectée :
     *   - inauguration : vendredi 02/10 matin (avant les ateliers de l'après-midi) ;
     *   - table ronde : lundi 05/10 de 8h à 10h ;
     *   - formation : mardi 06/10 de 14h à 17h ;
     *   - clôture : jeudi 08/10 en fin de matinée - DERNIER jour de l'événement,
     *     d'où l'absence de bloc d'ateliers ce matin-là (cf. le seeder de volume).
     *
     * ⚠️ Les cérémonies sont diffusées en visio **publique** : c'est le seul
     * endroit du jeu de démonstration où `MeetingVisibility::Public` est exercé
     * (un visiteur non inscrit peut suivre l'ouverture, pas un atelier).
     *
     * @param  list<User>  $moderators
     */
    private function buildPlenaries(Event $event, array $moderators, Carbon $start): void
    {
        // [libellé, décalage en jours, h début, min début, h fin, min fin, animateur, visio, visibilité]
        $plenaries = [
            ['Séance inaugurale', 0, 9, 0, 10, 30, 'Comité d’organisation', 'scheduled', 'public'],
            ['Table ronde : universités africaines et développement durable', 3, 8, 0, 10, 0, $moderators[0]->name, 'scheduled', 'public'],
            ['Formation : méthodologie de la recherche et valorisation scientifique', 4, 14, 0, 17, 0, 'Équipe de formation', 'scheduled', 'participants'],
            ['Séance de clôture et recommandations', 6, 11, 0, 12, 30, 'Comité scientifique', 'scheduled', 'public'],
        ];

        foreach ($plenaries as [$label, $day, $sh, $sm, $eh, $em, $animator, $meeting, $visibility]) {
            $begin = (clone $start)->addDays($day)->setTime($sh, $sm);
            $finish = (clone $start)->addDays($day)->setTime($eh, $em);

            EventSession::updateOrCreate(
                ['event_id' => $event->id, 'label' => $label],
                [
                    'atelier_id' => null,
                    'type' => 'plenary',
                    'room' => 'Grand amphithéâtre',
                    'session_day' => $begin->toDateString(),
                    'starts_at' => $begin,
                    'ends_at' => $finish,
                    // ⚠️ Fournisseur NON forcé : il suit `meetings.default`, et le
                    // registre retombe seul sur Jitsi public si ce défaut n'est pas
                    // configuré - forcer ici écraserait la configuration du site.
                    'meeting_provider' => null,
                    'meeting_status' => $meeting,
                    'meeting_visibility' => $visibility,
                    'moderator_name' => $animator,
                ]
            );
        }
    }

    /** Rattache (idempotent) un membre du staff à une événement avec une fonction. */
    private function assignStaff(User $user, Event $event, string $role): void
    {
        $exists = $user->assignedEditions()
            ->where('events.id', $event->id)
            ->wherePivot('role', $role)
            ->exists();

        if (! $exists) {
            $user->assignedEditions()->attach($event->id, ['role' => $role]);
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

    private function participant(Event $event, User $user, string $role, string $title): Participant
    {
        return Participant::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
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
     * Relecteur de test : compte (login) + inscription « reviewer » sur l'événement
     * avec jeton d'accès au portail de relecture. Garantit un jeton stable.
     */
    private function reviewer(Event $event, string $email, string $first, string $last): Participant
    {
        $user = $this->user($email, $first, $last);
        $participant = $this->participant($event, $user, 'reviewer', 'Enseignant / Chercheur');

        if (blank($participant->access_token)) {
            $participant->access_token = Str::random(64);
            $participant->save();
        }

        return $participant;
    }
}
