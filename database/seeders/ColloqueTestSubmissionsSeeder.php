<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Communications de TEST pour le colloque en cours (édition ouverte) : un éventail
 * réaliste de soumissions (nouvelles, en revue, corrections, acceptées, refusées)
 * réparties sur les axes de l'édition, avec avis de relecture et paiements — pour
 * pouvoir tester le back-office (pipeline, documents, livret, badges, paiements).
 *
 * Idempotent : ne fait rien si l'édition a déjà des soumissions.
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
    private array $grades = ['Doctorant' => 15000, 'Enseignant-chercheur' => 35000, 'Professeur' => 50000];

    public function run(): void
    {
        $edition = Edition::where('slug', 'edition-2026')->first()
            ?? Edition::where('status', 'open')->latest('id')->first()
            ?? Edition::latest('id')->first();

        if (! $edition) {
            $this->command?->warn('Aucune édition ouverte trouvée.');

            return;
        }

        if ($edition->submissions()->where('revision_of', 0)->exists()) {
            $this->command?->warn("L'édition #{$edition->id} a déjà des soumissions — seeder ignoré.");

            return;
        }

        $tracks = is_array($edition->settings['tracks'] ?? null) && $edition->settings['tracks'] !== []
            ? $edition->settings['tracks']
            : ['Axe thématique'];

        // Quelques relecteurs pour les avis en aveugle.
        $reviewers = collect(range(1, 4))->map(fn ($i) => $this->makeParticipant($edition, $i + 900, 'reviewer', 'Enseignant-chercheur'))->all();

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
            $participant = $this->makeParticipant($edition, $seq, 'author', $grade);
            $track = $tracks[array_rand($tracks)];
            $type = random_int(0, 3) === 0 ? 'poster' : 'oral';
            $createdAt = Carbon::now()->subDays(random_int(0, 40))->subHours(random_int(0, 23));

            $submission = Submission::create([
                'edition_id' => $edition->id,
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
                    'edition_id' => $edition->id,
                    'participant_id' => $participant->id,
                    'submission_id' => $submission->id,
                    'amount' => $this->grades[$grade],
                    'currency' => 'XOF',
                    'method' => ['cash', 'mobile_money', 'card'][array_rand(['cash', 'mobile_money', 'card'])],
                    'status' => 'paid',
                    'receipt_no' => sprintf('REC-%d-%04d', (int) ($edition->year ?: date('Y')), $paidSeq),
                    'paid_at' => Carbon::now()->subDays(random_int(1, 20)),
                ]);
            } elseif ($pendingBudget > 0 && in_array($status, ['under_review', 'revision'], true)) {
                $pendingBudget--;
                Payment::create([
                    'edition_id' => $edition->id,
                    'participant_id' => $participant->id,
                    'submission_id' => $submission->id,
                    'amount' => $this->grades[$grade],
                    'currency' => 'XOF',
                    'method' => 'mobile_money',
                    'status' => 'pending',
                ]);
            }
        }

        $this->command?->info("Communications de test ajoutées à l'édition #{$edition->id} : {$seq} soumissions.");
    }

    private function makeParticipant(Edition $edition, int $n, string $role, string $grade): Participant
    {
        $first = $this->firstNames[($n * 7) % count($this->firstNames)];
        $last = $this->lastNames[($n * 3) % count($this->lastNames)];

        return Participant::create([
            'edition_id' => $edition->id,
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
            default => [['minor', 'accept'], [12, 16], 'pending'], // under_review : avis en cours
        };

        foreach (collect($reviewers)->shuffle()->take(2) as $reviewer) {
            Review::create([
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewer->id,
                'recommendation' => $reviewStatus === 'pending' ? '' : $recos[array_rand($recos)],
                'score' => $reviewStatus === 'pending' ? null : random_int($range[0], $range[1]),
                'comments' => $reviewStatus === 'pending' ? '' : 'Communication '.($status === 'accepted' ? 'solide et pertinente' : ($status === 'rejected' ? 'insuffisamment aboutie' : 'à retravailler')).'.',
                'comments_private' => 'Avis confidentiel pour le comité.',
                'status' => $reviewStatus,
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
