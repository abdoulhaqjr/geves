<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Attestation;
use App\Models\Colloque;
use App\Models\CommitteeMember;
use App\Models\Edition;
use App\Models\Email;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Slot;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Scénario de démonstration complet pour deux colloques traversant tout le cycle
 * de vie — de l'appel à communications jusqu'à la clôture : comités, soumissions,
 * relecture en aveugle (avis notés + recommandations), décisions, paiements,
 * programme (ateliers / sessions / créneaux affectés, modérateurs & rapporteurs),
 * attestations (participation / communication / modération / rapporteur), galerie
 * et documents. Idempotent (réinitialise les données de chaque édition à la
 * relance). Données fictives — à ne pas exécuter en production.
 */
class DemoScenarioSeeder extends Seeder
{
    /** @var list<string> */
    private array $firstNames = [
        'Koffi', 'Ama', 'Kossi', 'Afi', 'Yao', 'Akou', 'Komla', 'Essi', 'Kodjo', 'Adjoa',
        'Mensah', 'Fatou', 'Ibrahim', 'Aïcha', 'Moussa', 'Nadia', 'Séna', 'Abra', 'Têko', 'Délali',
    ];

    /** @var list<string> */
    private array $lastNames = [
        'Agbeko', 'Lawson', 'Bamana', 'Tchalla', 'Akouété', 'Diallo', 'Sagna', 'Kponton', 'Amoussou',
        'Dossou', 'Adjovi', 'Gnassingbé', 'Ouédraogo', 'Traoré', 'Mensah', 'Alhou', 'Bello', 'Nubukpo',
    ];

    /** @var list<string> */
    private array $institutions = [
        'Université de Kara', 'Université de Lomé', 'Université de Parakou', 'Université de Ouagadougou',
        'Université Cheikh Anta Diop', 'Université Félix Houphouët-Boigny', 'ENS Atakpamé', 'INSAT Kara',
    ];

    /** @var array<string, int> */
    private array $grades = [
        'Enseignant-chercheur' => 30000,
        'Maître-assistant' => 25000,
        'Doctorant' => 15000,
        'Étudiant (M2)' => 10000,
    ];

    public function run(): void
    {
        $this->buildColloque([
            'colloque' => 'Colloque international pluridisciplinaire de l’Université de Kara',
            'colloque_slug' => 'colloque-pluridisciplinaire-uk',
            'edition_slug' => 'cip-uk-2023',
            'title' => 'Colloque international pluridisciplinaire — 2023',
            'edition_label' => 'VIe édition',
            'year' => 2023,
            'start' => '2023-11-08', 'end' => '2023-11-11',
            'location' => 'Campus de l’Université de Kara, Togo',
            'theme' => 'Recherche, innovation et développement durable',
            'subtitle' => 'Sciences, sociétés et transitions en Afrique de l’Ouest',
            'intro' => 'La VIe édition a réuni chercheurs, doctorants et institutions autour des transitions '
                .'scientifiques et sociétales. Trois jours de communications, ateliers et tables rondes.',
            'tracks' => ['Sciences & technologies', 'Sciences humaines & sociales', 'Santé & environnement', 'Éducation & formation'],
            'seed' => 'cip2023',
        ]);

        $this->buildColloque([
            'colloque' => 'Journées scientifiques de la DRI',
            'colloque_slug' => 'journees-scientifiques-dri',
            'edition_slug' => 'jsd-2024',
            'title' => 'Journées scientifiques de la DRI — 2024',
            'edition_label' => 'IIIe édition',
            'year' => 2024,
            'start' => '2024-06-18', 'end' => '2024-06-20',
            'location' => 'Amphithéâtre central · Université de Kara',
            'theme' => 'Numérique, société et transitions',
            'subtitle' => 'Intelligence artificielle, gouvernance et innovation',
            'intro' => 'Trois journées consacrées au numérique et à ses usages pour le développement : IA, '
                .'gouvernance des données, agriculture intelligente et humanités numériques.',
            'tracks' => ['Numérique & IA', 'Économie & gouvernance', 'Cultures & langues', 'Agriculture & climat'],
            'seed' => 'jsd2024',
        ]);

        // Édition « en cours » (déroulement en direct + carte « en cours » de l'accueil).
        $this->buildOngoingEdition();

        // Comptes de démonstration (staff back-office + participant connectable).
        $this->call(DemoStaffSeeder::class);
    }

    /** Édition dont les dates encadrent aujourd'hui, avec une session en direct. */
    private function buildOngoingEdition(): void
    {
        $colloque = Colloque::firstOrCreate(
            ['slug' => 'seminaire-doctoral-uk'],
            ['name' => 'Séminaire doctoral de l’Université de Kara']
        );

        $now = Carbon::now();
        $edition = Edition::updateOrCreate(['slug' => 'seminaire-en-cours'], [
            'colloque_id' => $colloque->id,
            'title' => 'Séminaire doctoral UK — édition en cours',
            'edition_label' => 'Session '.$now->year,
            'theme' => 'Méthodes de recherche et valorisation scientifique',
            'subtitle' => 'Sessions en présentiel et hybride',
            'year' => (int) $now->format('Y'),
            'date_start' => $now->copy()->subDay(),
            'date_end' => $now->copy()->addDays(2),
            'location' => 'Amphithéâtre A · Université de Kara',
            'status' => 'open',
            'submission_open' => false,
            'banner_url' => 'https://picsum.photos/seed/seminaire-live/1200/500',
            'settings' => [
                'announcements' => [
                    ['title' => 'Bienvenue', 'text' => 'La séance plénière débute à l’Amphi A.'],
                    ['title' => 'Pause café', 'text' => 'Hall principal à 10h30.'],
                ],
            ],
        ]);

        $this->resetEdition($edition);

        // Quelques communicants + communications acceptées.
        $participants = [];
        foreach (range(1, 6) as $i) {
            $name = $this->name();
            [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'X');
            $p = Participant::create([
                'edition_id' => $edition->id,
                'first_name' => $first, 'last_name' => $last,
                'email' => Str::lower($first.'.'.$last.'.live'.$i).'@example.tg',
                'institution' => $this->institutions[array_rand($this->institutions)],
                'country' => 'Togo', 'title' => 'Doctorant', 'role' => 'author',
                'access_token' => Str::random(64),
            ]);
            $edition->submissions()->create([
                'participant_id' => $p->id,
                'ref_code' => sprintf('SEM-%03d', $i),
                'access_token' => Str::random(64),
                'title' => $this->submissionTitle('Méthodologie'),
                'abstract' => 'Résumé de la communication présentée en session.',
                'track' => 'Méthodologie', 'type' => 'oral', 'status' => 'accepted',
                'revision_of' => 0, 'version' => 1,
            ]);
            $participants[] = $p;
        }

        $atelier = $edition->ateliers()->create(['title' => 'Atelier — Méthodes de recherche', 'tracks' => ['Méthodologie'], 'ordre' => 0]);

        // Session EN DIRECT (encadre l'instant présent) + une session à venir.
        $edition->sessions()->create([
            'atelier_id' => $atelier->id, 'type' => 'plenary', 'label' => 'Plénière d’ouverture',
            'room' => 'Amphi A', 'session_day' => $now->toDateString(),
            'starts_at' => $now->copy()->subMinutes(20), 'ends_at' => $now->copy()->addMinutes(40),
            'moderator_name' => $this->name(), 'moderator_email' => 'moderateur@example.tg',
            'rapporteur_name' => $this->name(), 'rapporteur_email' => 'rapporteur@example.tg',
            'meeting_status' => 'live', 'meeting_visibility' => 'public', 'ordre' => 0,
        ]);
        $edition->sessions()->create([
            'atelier_id' => $atelier->id, 'type' => 'atelier', 'label' => 'Atelier méthodologie',
            'room' => 'Salle B', 'session_day' => $now->toDateString(),
            'starts_at' => $now->copy()->addHours(2), 'ends_at' => $now->copy()->addHours(4),
            'ordre' => 1,
        ]);
    }

    /** @param array<string, mixed> $cfg */
    private function buildColloque(array $cfg): void
    {
        $colloque = Colloque::firstOrCreate(
            ['slug' => $cfg['colloque_slug']],
            ['name' => $cfg['colloque']]
        );

        $start = Carbon::parse($cfg['start']);
        $end = Carbon::parse($cfg['end']);

        $edition = Edition::updateOrCreate(['slug' => $cfg['edition_slug']], [
            'colloque_id' => $colloque->id,
            'title' => $cfg['title'],
            'edition_label' => $cfg['edition_label'],
            'theme' => $cfg['theme'],
            'subtitle' => $cfg['subtitle'],
            'year' => $cfg['year'],
            'date_start' => $start,
            'date_end' => $end,
            'location' => $cfg['location'],
            'status' => 'archived',
            'submission_open' => false,
            'submission_deadline' => $start->copy()->subMonths(3),
            'banner_url' => "https://picsum.photos/seed/{$cfg['seed']}-banner/1200/500",
            'booklet_url' => "https://picsum.photos/seed/{$cfg['seed']}-booklet/800/1100",
            'settings' => [
                'intro' => $cfg['intro'],
                'highlights' => ['+120 communications', '4 conférences plénières', '10 ateliers thématiques', '8 pays représentés'],
                'tracks' => $cfg['tracks'],
                'fees' => $this->grades,
            ],
        ]);

        $this->resetEdition($edition);

        $this->seedCommittee($edition, $cfg['seed']);
        [$reviewers, $chairs] = $this->seedStaff($edition);
        $submissions = $this->seedSubmissionsAndReviews($edition, $cfg['tracks'], $reviewers);
        $this->seedPayments($edition, $submissions);
        $this->seedProgram($edition, $cfg['tracks'], $submissions, $chairs, $start);
        $this->seedAttestations($edition, $submissions, $chairs);
        $this->seedMedia($edition, $cfg['seed']);
        $this->seedEmails($edition, $submissions);
    }

    /** Réinitialise toutes les données dépendantes d'une édition (idempotence). */
    private function resetEdition(Edition $edition): void
    {
        Review::whereIn('submission_id', $edition->submissions()->pluck('id'))->delete();
        Slot::where('edition_id', $edition->id)->delete();
        Payment::where('edition_id', $edition->id)->delete();
        Attestation::where('edition_id', $edition->id)->delete();
        Submission::where('edition_id', $edition->id)->delete();
        EventSession::where('edition_id', $edition->id)->delete();
        Atelier::where('edition_id', $edition->id)->delete();
        Participant::where('edition_id', $edition->id)->delete();
        CommitteeMember::where('edition_id', $edition->id)->delete();
        $edition->media()->delete();
        Email::where('edition_id', $edition->id)->delete();
    }

    private function name(): string
    {
        return $this->firstNames[array_rand($this->firstNames)].' '.$this->lastNames[array_rand($this->lastNames)];
    }

    private function seedCommittee(Edition $edition, string $seed): void
    {
        $roles = ['supervision', 'scientifique', 'scientifique', 'scientifique', 'organisation', 'organisation'];
        foreach ($roles as $i => $role) {
            [$first, $last] = explode(' ', $this->name().' x');
            CommitteeMember::create([
                'edition_id' => $edition->id,
                'person_name' => ($role === 'supervision' ? 'Pr ' : ($role === 'scientifique' ? 'Dr ' : '')).$first.' '.$last,
                'person_title' => $role === 'supervision' ? 'Président du comité' : ($role === 'scientifique' ? 'Membre du comité scientifique' : 'Coordination'),
                'institution' => $this->institutions[array_rand($this->institutions)],
                'email' => Str::lower($first.'.'.$last).'@univkara.tg',
                'role' => $role,
                'photo_url' => "https://i.pravatar.cc/160?u={$seed}-com-{$i}",
                'ordre' => $i,
            ]);
        }
    }

    /**
     * Relecteurs et présidents de session (modérateurs / rapporteurs).
     *
     * @return array{0: list<Participant>, 1: list<Participant>}
     */
    private function seedStaff(Edition $edition): array
    {
        $reviewers = [];
        foreach (range(1, 5) as $i) {
            $name = $this->name();
            [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'X');
            $reviewers[] = Participant::create([
                'edition_id' => $edition->id,
                'first_name' => $first, 'last_name' => $last,
                'email' => Str::lower($first.'.'.$last.'.rev').'@example.tg',
                'institution' => $this->institutions[array_rand($this->institutions)],
                'country' => 'Togo', 'title' => 'Enseignant-chercheur', 'role' => 'reviewer', 'access_token' => Str::random(64),
            ]);
        }

        $chairs = [];
        foreach (['moderator', 'moderator', 'moderator', 'rapporteur', 'rapporteur', 'rapporteur'] as $i => $role) {
            $name = $this->name();
            [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'X');
            $chairs[] = Participant::create([
                'edition_id' => $edition->id,
                'first_name' => $first, 'last_name' => $last,
                'email' => Str::lower($first.'.'.$last.'.'.$role).'@example.tg',
                'institution' => $this->institutions[array_rand($this->institutions)],
                'country' => 'Togo', 'title' => 'Maître-assistant', 'role' => $role, 'access_token' => Str::random(64),
            ]);
        }

        return [$reviewers, $chairs];
    }

    /**
     * Auteurs, soumissions (statuts décidés) et relectures en aveugle.
     *
     * @param  list<string>  $tracks
     * @param  list<Participant>  $reviewers
     * @return list<Submission>
     */
    private function seedSubmissionsAndReviews(Edition $edition, array $tracks, array $reviewers): array
    {
        // Distribution finale (édition clôturée) : 14 acceptées, 4 refusées, 2 en révision.
        $statuses = array_merge(array_fill(0, 14, 'accepted'), array_fill(0, 4, 'rejected'), array_fill(0, 2, 'revision'));
        $gradeKeys = array_keys($this->grades);
        $submissions = [];

        foreach ($statuses as $n => $status) {
            $name = $this->name();
            [$first, $last] = array_pad(explode(' ', $name, 2), 2, 'X');
            $grade = $gradeKeys[array_rand($gradeKeys)];

            $participant = Participant::create([
                'edition_id' => $edition->id,
                'first_name' => $first, 'last_name' => $last,
                'email' => Str::lower($first.'.'.$last.$n).'@example.tg',
                'phone' => '+228 9'.random_int(1000000, 9999999),
                'institution' => $this->institutions[array_rand($this->institutions)],
                'country' => 'Togo', 'title' => $grade, 'role' => 'author', 'access_token' => Str::random(64),
            ]);

            $track = $tracks[array_rand($tracks)];
            $type = random_int(0, 3) === 0 ? 'poster' : 'oral';

            $submission = Submission::create([
                'edition_id' => $edition->id,
                'participant_id' => $participant->id,
                'ref_code' => sprintf('%s-%03d', Str::upper(Str::substr($edition->slug, 0, 2)), $n + 1),
                'access_token' => Str::random(64),
                'title' => $this->submissionTitle($track),
                'authors' => [
                    ['name' => $participant->full_name, 'email' => $participant->email, 'affiliation' => $participant->institution, 'corresponding' => true],
                    ['name' => $this->name(), 'email' => Str::lower('co'.$n).'@example.tg', 'affiliation' => $this->institutions[array_rand($this->institutions)], 'corresponding' => false],
                ],
                'abstract' => 'Cette communication présente '.Str::lower($this->submissionTitle($track)).'. '
                    .'Le contexte, la méthodologie mobilisée et les principaux résultats sont exposés, '
                    .'ainsi que les perspectives pour la recherche et les politiques publiques en Afrique de l’Ouest.',
                'keywords' => implode(', ', array_slice(['innovation', 'développement', 'numérique', 'éducation', 'santé', 'gouvernance'], 0, 3)),
                'track' => $track,
                'type' => $type,
                'status' => $status,
                'revision_of' => 0,
                'version' => 1,
            ]);

            // Deux avis en aveugle alignés sur la décision.
            [$recos, $range] = match ($status) {
                'accepted' => [['accept', 'minor'], [14, 18]],
                'rejected' => [['reject', 'major'], [6, 10]],
                default => [['minor', 'major'], [11, 13]],
            };
            $picked = collect($reviewers)->shuffle()->take(2);
            foreach ($picked as $reviewer) {
                Review::create([
                    'submission_id' => $submission->id,
                    'reviewer_id' => $reviewer->id,
                    'recommendation' => $recos[array_rand($recos)],
                    'score' => random_int($range[0], $range[1]),
                    'comments' => 'Communication '.($status === 'accepted' ? 'solide et pertinente' : ($status === 'rejected' ? 'insuffisamment aboutie' : 'à retravailler')).' ; méthodologie '.($status === 'accepted' ? 'rigoureuse' : 'perfectible').'.',
                    'comments_private' => 'Avis confidentiel pour le comité.',
                    'status' => 'completed',
                ]);
            }

            $submissions[] = $submission;
        }

        return $submissions;
    }

    /**
     * Paiements des frais pour les communications acceptées (tarif du grade).
     *
     * @param  list<Submission>  $submissions
     */
    private function seedPayments(Edition $edition, array $submissions): void
    {
        $methods = ['cash', 'mobile_money', 'card'];
        $seq = 0;
        foreach ($submissions as $submission) {
            if ($submission->status !== 'accepted') {
                continue;
            }
            $participant = $submission->participant;
            $amount = $this->grades[$participant->title] ?? 20000;
            $seq++;

            Payment::create([
                'edition_id' => $edition->id,
                'participant_id' => $participant->id,
                'submission_id' => $submission->id,
                'amount' => $amount,
                'currency' => 'XOF',
                'method' => $methods[array_rand($methods)],
                'status' => 'paid',
                'receipt_no' => sprintf('REC-%d-%04d', $edition->year, $seq),
                'paid_at' => $edition->date_start->copy()->subWeeks(random_int(1, 6)),
            ]);
        }
    }

    /**
     * Programme : ateliers, sessions (modérateur + rapporteur) et créneaux affectés.
     *
     * @param  list<string>  $tracks
     * @param  list<Submission>  $submissions
     * @param  list<Participant>  $chairs
     */
    private function seedProgram(Edition $edition, array $tracks, array $submissions, array $chairs, Carbon $start): void
    {
        $moderators = array_values(array_filter($chairs, fn ($c) => $c->role === 'moderator'));
        $rapporteurs = array_values(array_filter($chairs, fn ($c) => $c->role === 'rapporteur'));

        // Séance plénière d'ouverture.
        EventSession::create([
            'edition_id' => $edition->id, 'type' => 'plenary', 'label' => 'Séance plénière d’ouverture',
            'room' => 'Amphi A', 'session_day' => $start->toDateString(),
            'starts_at' => $start->copy()->setTime(9, 0), 'ends_at' => $start->copy()->setTime(10, 30),
            'moderator_name' => $moderators[0]->full_name, 'moderator_email' => $moderators[0]->email,
            'rapporteur_name' => $rapporteurs[0]->full_name, 'rapporteur_email' => $rapporteurs[0]->email,
            'ordre' => 0,
        ]);

        $accepted = array_values(array_filter($submissions, fn ($s) => $s->status === 'accepted'));
        $chunks = array_chunk($accepted, (int) ceil(count($accepted) / 3));

        foreach (array_slice($tracks, 0, 3) as $i => $track) {
            $atelier = Atelier::create([
                'edition_id' => $edition->id, 'title' => 'Atelier — '.$track,
                'tracks' => [$track], 'ordre' => $i,
            ]);

            $day = $start->copy()->addDays($i % 3);
            $session = EventSession::create([
                'edition_id' => $edition->id, 'atelier_id' => $atelier->id, 'type' => 'atelier',
                'label' => 'Session '.($i + 1).' — '.$track, 'room' => 'Salle '.chr(66 + $i),
                'session_day' => $day->toDateString(),
                'starts_at' => $day->copy()->setTime(11, 0), 'ends_at' => $day->copy()->setTime(13, 0),
                'moderator_name' => $moderators[$i % count($moderators)]->full_name,
                'moderator_email' => $moderators[$i % count($moderators)]->email,
                'rapporteur_name' => $rapporteurs[$i % count($rapporteurs)]->full_name,
                'rapporteur_email' => $rapporteurs[$i % count($rapporteurs)]->email,
                'ordre' => $i + 1,
            ]);

            // Créneaux : chaque communication acceptée du groupe dans la session.
            foreach ($chunks[$i] ?? [] as $j => $submission) {
                $slotStart = $session->starts_at->copy()->addMinutes(20 * $j);
                Slot::create([
                    'edition_id' => $edition->id,
                    'session_id' => $session->id,
                    'session_label' => $session->label,
                    'room' => $session->room,
                    'starts_at' => $slotStart,
                    'ends_at' => $slotStart->copy()->addMinutes(20),
                    'submission_id' => $submission->id,
                    'participant_id' => $submission->participant_id,
                    'chosen_by_participant' => false,
                ]);
            }
        }
    }

    /**
     * Attestations : participation (payés), communication (acceptés),
     * modération et rapporteur (présidents de session).
     *
     * @param  list<Submission>  $submissions
     * @param  list<Participant>  $chairs
     */
    private function seedAttestations(Edition $edition, array $submissions, array $chairs): void
    {
        foreach ($submissions as $submission) {
            if ($submission->status !== 'accepted') {
                continue;
            }
            $this->issue($submission->participant, 'participation');
            $this->issue($submission->participant, 'communication');
        }

        foreach ($chairs as $chair) {
            $this->issue($chair, $chair->role === 'moderator' ? 'moderation' : 'rapporteur');
        }
    }

    private function issue(Participant $participant, string $type): void
    {
        Attestation::create([
            'edition_id' => $participant->edition_id,
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'participant_id' => $participant->id,
            'recipient_name' => $participant->full_name,
            'recipient_email' => $participant->email,
            'revoked' => false,
            'issued_at' => now(),
        ]);
    }

    private function seedMedia(Edition $edition, string $seed): void
    {
        $edition->media()->create(['type' => 'video', 'title' => 'Ouverture officielle', 'url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ', 'ordre' => 0]);
        foreach (range(1, 9) as $i) {
            $edition->media()->create(['type' => 'photo', 'title' => 'Moment '.$i, 'url' => "https://picsum.photos/seed/{$seed}-{$i}/600/400", 'ordre' => $i]);
        }
        $edition->media()->create(['type' => 'document', 'title' => 'Appel à communications (PDF)', 'url' => "https://picsum.photos/seed/{$seed}-aac/800/1100", 'ordre' => 20]);
        $edition->media()->create(['type' => 'document', 'title' => 'Actes du colloque', 'url' => "https://picsum.photos/seed/{$seed}-actes/800/1100", 'ordre' => 21]);
    }

    /** @param list<Submission> $submissions */
    private function seedEmails(Edition $edition, array $submissions): void
    {
        $sample = array_slice($submissions, 0, 6);
        foreach ($sample as $submission) {
            $accepted = $submission->status === 'accepted';
            Email::create([
                'edition_id' => $edition->id,
                'participant_id' => $submission->participant_id,
                'template' => $accepted ? 'submission_accepted' : 'submission_status',
                'recipient' => $submission->participant->email,
                'subject' => $accepted ? 'Votre communication est acceptée' : 'Décision sur votre communication',
                'body' => 'Bonjour, la décision du comité concernant « '.$submission->title.' » est disponible dans votre espace.',
                'status' => 'sent',
                'sent_at' => $edition->date_start->copy()->subMonths(2),
            ]);
        }
    }

    private function submissionTitle(string $track): string
    {
        $themes = [
            'l’apport du numérique', 'les déterminants socio-économiques', 'une approche interdisciplinaire',
            'les enjeux de gouvernance', 'l’innovation pédagogique', 'la valorisation de la recherche',
            'les transitions écologiques', 'l’intelligence artificielle appliquée',
        ];

        return ucfirst($themes[array_rand($themes)]).' dans le champ « '.$track.' »';
    }
}
