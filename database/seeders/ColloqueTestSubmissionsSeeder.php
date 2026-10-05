<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Communications de TEST pour le colloque en cours (événement ouvert) : un éventail
 * réaliste de soumissions (nouvelles, en revue, corrections, acceptées, refusées)
 * réparties sur les axes de l'événement, avec avis de relecture et paiements - pour
 * pouvoir tester le back-office (pipeline, documents, livret, badges, paiements).
 *
 * Idempotent : ne fait rien si l'événement a déjà des soumissions.
 * Lancement : php artisan db:seed --class=ColloqueTestSubmissionsSeeder
 */
class ColloqueTestSubmissionsSeeder extends Seeder
{
    /** @var list<string> */
    private array $firstNames = ['Awa', 'Koffi', 'Sena', 'Jean', 'Mariam', 'Paul', 'Fatou', 'Yawovi', 'Aïcha', 'Komla', 'Adjoa', 'Kossi', 'Rahim', 'Ama', 'Bénédicte', 'Élie', 'Nadège', 'Ousmane', 'Rachel', 'Sylvain'];

    /** @var list<string> */
    private array $lastNames = ['Mensah', 'Tchalim', 'Bakou', 'Ouattara', 'Adjei', 'Dossou', 'Ndiaye', 'Kuevi', 'Agbeko', 'Lawson', 'Amétépé', 'Gnassingbé', 'Traoré', 'Békalé', 'Sow', 'Kponton', 'Afawoubo', 'Diallo', 'Kabore', 'Zinsou'];

    /** @var list<string> */
    private array $institutions = ['Université de Kara', 'Université de Parakou', 'Université de Lomé', 'University of Ghana', 'Université d’Abomey-Calavi', 'UCAD Dakar', 'Université de Ouagadougou', 'CIRAD / IRD'];

    /** @var array<string, int> grade => tarif FCFA */
    private array $grades = ['Doctorant' => 15000, 'Enseignant / Chercheur' => 35000, 'Professeur' => 50000];

    public function run(): void
    {
        $event = Event::where('slug', 'edition-2026')->first()
            ?? Event::where('status', 'open')->latest('id')->first()
            ?? Event::latest('id')->first();

        if (! $event) {
            $this->command?->warn('Aucun événement ouvert trouvée.');

            return;
        }

        if ($event->submissions()->where('revision_of', 0)->exists()) {
            $this->command?->warn("L'événement #{$event->id} a déjà des soumissions - seeder ignoré.");

            return;
        }

        $tracks = is_array($event->settings['tracks'] ?? null) && $event->settings['tracks'] !== []
            ? $event->settings['tracks']
            : ['Axe thématique'];

        // Quelques relecteurs pour les avis en aveugle.
        $reviewers = collect(range(1, 4))->map(fn ($i) => $this->makeParticipant($event, $i + 900, 'reviewer', 'Enseignant / Chercheur'))->all();

        // Éventail de statuts d'un colloque EN COURS (soumissions encore ouvertes).
        $statuses = array_merge(
            array_fill(0, 7, 'accepted'),
            array_fill(0, 5, 'under_review'),
            array_fill(0, 4, 'submitted'),
            array_fill(0, 2, 'revision'),
            array_fill(0, 2, 'rejected'),
        );

        $gradeKeys = array_keys($this->grades);
        $seq = 0;
        $paidSeq = 0;
        $pendingBudget = 3; // quelques paiements en attente (pour tester les relances)

        foreach ($statuses as $status) {
            $seq++;
            $grade = $gradeKeys[array_rand($gradeKeys)];
            $participant = $this->makeParticipant($event, $seq, 'author', $grade);
            $track = $tracks[array_rand($tracks)];
            $type = random_int(0, 3) === 0 ? 'poster' : 'oral';
            $createdAt = Carbon::now()->subDays(random_int(0, 40))->subHours(random_int(0, 23));

            $submission = Submission::create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'ref_code' => sprintf('UKC-S-%04d', $seq),
                'access_token' => Str::random(64),
                'title' => $this->title($track),
                'authors' => [
                    ['name' => $participant->full_name, 'email' => $participant->email, 'affiliation' => $participant->institution, 'corresponding' => true],
                    ['name' => $this->firstNames[array_rand($this->firstNames)].' '.$this->lastNames[array_rand($this->lastNames)], 'email' => Str::lower('co'.$seq).'@example.tg', 'affiliation' => $this->institutions[array_rand($this->institutions)], 'corresponding' => false],
                ],
                'abstract' => 'Cette communication étudie '.Str::lower($this->title($track)).'. '
                    ."Le contexte, la méthodologie et les principaux résultats sont présentés, ainsi que les perspectives pour la recherche et l'action publique en Afrique de l'Ouest.",
                'keywords' => implode(', ', ['innovation', 'développement durable', $track]),
                'track' => $track,
                'type' => $type,
                'status' => $status,
                'revision_of' => 0,
                'version' => 1,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $this->seedReviews($submission, $status, $reviewers);

            // Paiements : acceptées => payé ; quelques autres => en attente.
            if ($status === 'accepted') {
                $paidSeq++;
                Payment::create([
                    'event_id' => $event->id,
                    'participant_id' => $participant->id,
                    'submission_id' => $submission->id,
                    'amount' => $this->grades[$grade],
                    'currency' => 'XOF',
                    'method' => ['cash', 'mobile_money', 'card'][array_rand(['cash', 'mobile_money', 'card'])],
                    'status' => 'paid',
                    'receipt_no' => sprintf('REC-%d-%04d', (int) ($event->year ?: date('Y')), $paidSeq),
                    'paid_at' => Carbon::now()->subDays(random_int(1, 20)),
                ]);
            } elseif ($pendingBudget > 0 && in_array($status, ['under_review', 'revision'], true)) {
                $pendingBudget--;
                Payment::create([
                    'event_id' => $event->id,
                    'participant_id' => $participant->id,
                    'submission_id' => $submission->id,
                    'amount' => $this->grades[$grade],
                    'currency' => 'XOF',
                    'method' => 'mobile_money',
                    'status' => 'pending',
                ]);
            }
        }

        $this->command?->info("Communications de test ajoutées à l'événement #{$event->id} : {$seq} soumissions.");
    }

    private function makeParticipant(Event $event, int $n, string $role, string $grade): Participant
    {
        $first = $this->firstNames[($n * 7) % count($this->firstNames)];
        $last = $this->lastNames[($n * 3) % count($this->lastNames)];

        return Participant::create([
            'event_id' => $event->id,
            'first_name' => $first,
            'last_name' => $last,
            'email' => Str::lower($first.'.'.$last.'.'.$n).'@example.tg',
            'phone' => '+228 9'.random_int(1000000, 9999999),
            'institution' => $this->institutions[array_rand($this->institutions)],
            'country' => 'Togo',
            'title' => $grade,
            'role' => $role,
            'access_token' => Str::random(64),
        ]);
    }

    /** @param  list<Participant>  $reviewers */
    private function seedReviews(Submission $submission, string $status, array $reviewers): void
    {
        // Les nouvelles soumissions n'ont pas encore d'avis.
        if ($status === 'submitted') {
            return;
        }

        [$recos, $range, $reviewStatus] = match ($status) {
            'accepted' => [['accept', 'minor'], [14, 18], 'completed'],
            'rejected' => [['reject', 'major'], [6, 10], 'completed'],
            'revision' => [['minor', 'major'], [11, 13], 'completed'],
            // under_review : relecture confiée, pas encore rendue. ⚠️ Le statut
            // doit appartenir à Review::STATUSES - un statut inventé sort des
            // compteurs du comité (ni actif, ni en attente, ni en retard).
            default => [['minor', 'accept'], [12, 16], 'invited'],
        };
        $done = $reviewStatus === 'completed';

        // Une relecture en cours porte une échéance : de quoi voir à la fois un
        // avis dans les temps et un avis en retard dans le jeu de démonstration.
        $offsets = [10, -4];

        foreach (collect($reviewers)->shuffle()->take(2)->values() as $i => $reviewer) {
            Review::create([
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewer->id,
                'recommendation' => $done ? $recos[array_rand($recos)] : '',
                'score' => $done ? random_int($range[0], $range[1]) : null,
                'comments' => $done ? 'Communication '.($status === 'accepted' ? 'solide et pertinente' : ($status === 'rejected' ? 'insuffisamment aboutie' : 'à retravailler')).'.' : '',
                'comments_private' => 'Avis confidentiel pour le comité.',
                'status' => $reviewStatus,
                'assigned_at' => now()->subDays(14),
                'due_at' => $done ? null : now()->addDays($offsets[$i] ?? 10),
            ]);
        }
    }

    private function title(string $track): string
    {
        $templates = [
            'Innovations et durabilité dans le champ « %s »',
            'Approches interdisciplinaires en %s',
            'Numérique et transition dans le domaine %s',
            'Formation et recherche : enjeux pour %s',
            'Politiques publiques et %s en Afrique de l’Ouest',
        ];

        return sprintf($templates[array_rand($templates)], $track);
    }
}
