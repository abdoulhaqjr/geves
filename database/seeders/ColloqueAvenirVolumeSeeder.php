<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\User;
use App\Support\Fees;
use App\Support\ParticipantTypes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Scénario « à l'échelle » du dernier colloque des seeders : le VIIIe colloque
 * conjoint Parakou-Kara (cf. {@see ColloqueAvenirSeeder}, qui crée l'édition,
 * ses 6 ateliers et le direct).
 *
 * Ce seeder lui donne le VOLUME d'un vrai colloque : 200 communications, un
 * auteur correspondant par communication, et **93 % de paiements de
 * participation validés manuellement** (encaissement sur place, comme au
 * secrétariat). De quoi éprouver la pagination, les filtres, les rapports,
 * l'attribution des relecteurs et la planification sur des données réalistes.
 *
 *   php artisan db:seed --class=ColloqueAvenirVolumeSeeder
 *
 * Idempotent : relancer ne duplique rien (clés stables ref_code / e-mail).
 * Tous les mots de passe : « password ».
 */
class ColloqueAvenirVolumeSeeder extends Seeder
{
    /** Volume visé de communications. */
    private const TOTAL = 200;

    /** Part des communications dont les frais sont encaissés ET validés. */
    private const PAID_RATIO = 0.93;

    /** Répartition des statuts (le reste bascule en « accepted »). */
    private const STATUS_MIX = [
        'submitted' => 18,
        'under_review' => 26,
        'revision' => 12,
        'rejected' => 14,
    ];

    /** Prénoms et noms, combinés pour fabriquer des auteurs plausibles. */
    private const FIRST_NAMES = [
        'Kossi', 'Afi', 'Yao', 'Akouvi', 'Komla', 'Ama', 'Sena', 'Edem', 'Mawuli', 'Essi',
        'Abdou', 'Fatou', 'Ibrahim', 'Aïcha', 'Moussa', 'Rachida', 'Serge', 'Nadia', 'Pierre', 'Clarisse',
    ];

    private const LAST_NAMES = [
        'Adjo', 'Mensah', 'Kodjo', 'Lawson', 'Agbeko', 'Sow', 'Traoré', 'Diallo', 'Kponton', 'Amegan',
        'Bakari', 'Ouédraogo', 'Nikiema', 'Sagna', 'Doumbia', 'Zongo', 'Koffi', 'Tchalla', 'Bassabi', 'Djato',
    ];

    private const INSTITUTIONS = [
        'Université de Kara', 'Université de Parakou', 'Université de Lomé',
        'Université d’Abomey-Calavi', 'ENS Atakpamé', 'INSTI Lokossa',
    ];

    public function run(): void
    {
        $edition = Edition::where('slug', 'avenir-durable-2025')->first()
            ?? Edition::whereHas('colloque', fn ($q) => $q->where('slug', 'parakou-kara'))->latest('id')->first();

        if (! $edition) {
            $this->command?->error('Édition introuvable : lancez d’abord ColloqueAvenirSeeder.');

            return;
        }

        $ateliers = $edition->ateliers()->orderBy('ordre')->get();
        if ($ateliers->isEmpty()) {
            $this->command?->error('Aucun atelier sur l’édition : lancez d’abord ColloqueAvenirSeeder.');

            return;
        }

        $tracks = collect($edition->settings['tracks'] ?? [])->values();
        $grades = ParticipantTypes::all();
        $recorder = User::where('email', 'admin@univkara.tg')->first();

        // Séances d'accueil des communications : une par atelier et par jour, afin
        // d'avoir assez de créneaux pour placer les communications acceptées.
        $sessions = $this->buildSessions($edition, $ateliers);

        // Tirage reproductible : relancer le seeder donne exactement le même jeu.
        mt_srand(20260928);

        $statuses = $this->statusDeck();
        $paidFlags = $this->paidDeck();

        $slotCursor = [];
        $created = 0;

        DB::transaction(function () use (
            $edition, $tracks, $grades, $recorder, $sessions,
            $statuses, $paidFlags, &$slotCursor, &$created
        ) {
            for ($i = 0; $i < self::TOTAL; $i++) {
                $n = $i + 1;
                $status = $statuses[$i];
                $isPaid = $paidFlags[$i];

                $first = self::FIRST_NAMES[$i % count(self::FIRST_NAMES)];
                $last = self::LAST_NAMES[intdiv($i, count(self::FIRST_NAMES)) % count(self::LAST_NAMES)];
                $email = sprintf('auteur%03d@avenir-durable.tg', $n);
                $grade = $grades[$i % count($grades)];
                $institution = self::INSTITUTIONS[$i % count(self::INSTITUTIONS)];
                $track = $tracks[$i % max(1, $tracks->count())] ?? '';

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $first.' '.$last,
                        'first_name' => $first,
                        'last_name' => $last,
                        'password' => Hash::make('password'),
                        'email_verified_at' => now(),
                        'access_level' => User::ACCESS_USER,
                        'affiliation' => $institution,
                        'grade' => $grade,
                        // ⚠️ `users.phone` est unique : un numéro par auteur.
                        'phone' => sprintf('+2289%06d', 100000 + $n),
                    ]
                );

                $participant = Participant::updateOrCreate(
                    ['edition_id' => $edition->id, 'user_id' => $user->id],
                    [
                        'first_name' => $first,
                        'last_name' => $last,
                        'email' => $email,
                        'title' => $grade,
                        'role' => 'author',
                        'institution' => $institution,
                        'country' => 'Togo',
                    ]
                );

                $submission = Submission::updateOrCreate(
                    ['edition_id' => $edition->id, 'ref_code' => sprintf('AVD-%03d', $n)],
                    [
                        'participant_id' => $participant->id,
                        'access_token' => Str::random(40),
                        'title' => $this->title($n, $track),
                        'authors' => [['name' => $first.' '.$last, 'email' => $email, 'affiliation' => $institution, 'corresponding' => true]],
                        'abstract' => $this->abstract($track),
                        'keywords' => 'innovation, durabilité, Afrique de l’Ouest',
                        'track' => $track,
                        'type' => $n % 7 === 0 ? 'poster' : 'oral',
                        'status' => $status,
                        // ⚠️ `revision_of` doit être posé explicitement (cf. CLAUDE.md).
                        'revision_of' => 0,
                    ]
                );
                $created++;

                if ($isPaid) {
                    $this->payManually($edition, $participant, $recorder, $n);
                }

                // Communication acceptée : on lui réserve un créneau.
                if ($status === 'accepted') {
                    $this->schedule($edition, $sessions, $participant, $submission, $slotCursor);
                }

                // Communications en cours d'évaluation : relectures confiées.
                if (in_array($status, ['under_review', 'revision'], true)) {
                    $this->review($edition, $submission, $i);
                }
            }
        });

        // ⚠️ On compte les paiements DU SCÉNARIO : l'édition en portait déjà
        // quelques-uns (cf. ColloqueAvenirSeeder), et les additionner donnerait
        // un taux faussé.
        $participantIds = Submission::where('edition_id', $edition->id)
            ->where('ref_code', 'like', 'AVD-%')->pluck('participant_id');
        $paid = Payment::where('edition_id', $edition->id)
            ->whereIn('participant_id', $participantIds)
            ->where('gateway', 'manual')->where('status', 'paid')->count();

        $this->command?->info(sprintf(
            '%d communications sur « %s » · %d paiements validés manuellement (%.1f %%).',
            $created,
            $edition->title,
            $paid,
            $created > 0 ? $paid / $created * 100 : 0,
        ));
    }

    /**
     * Statuts tirés dans un ordre stable : le mélange décrit STATUS_MIX, le
     * reste est accepté (le colloque se tient, l'essentiel est arbitré).
     *
     * @return list<string>
     */
    private function statusDeck(): array
    {
        $deck = [];
        foreach (self::STATUS_MIX as $status => $count) {
            $deck = array_merge($deck, array_fill(0, $count, $status));
        }
        $deck = array_merge($deck, array_fill(0, self::TOTAL - count($deck), 'accepted'));
        shuffle($deck);

        return $deck;
    }

    /**
     * 93 % de paiements validés, répartis régulièrement plutôt que groupés en
     * tête de liste (sinon la pagination donnerait une fausse impression).
     *
     * @return list<bool>
     */
    private function paidDeck(): array
    {
        $paid = (int) round(self::TOTAL * self::PAID_RATIO);
        $deck = array_merge(
            array_fill(0, $paid, true),
            array_fill(0, self::TOTAL - $paid, false),
        );
        shuffle($deck);

        return $deck;
    }

    /**
     * Encaissement validé au secrétariat : passerelle « manual », statut payé,
     * reçu numéroté et agent enregistreur - la forme exacte que produit
     * l'encaissement manuel du back-office.
     */
    private function payManually(Edition $edition, Participant $participant, ?User $recorder, int $n): void
    {
        $methods = array_keys(Payment::MANUAL_METHODS);

        Payment::updateOrCreate(
            ['edition_id' => $edition->id, 'participant_id' => $participant->id],
            [
                'amount' => Fees::resolveAmount($edition, $participant->title) ?? 0,
                'currency' => 'XOF',
                'method' => $methods[$n % count($methods)],
                'gateway' => 'manual',
                'status' => 'paid',
                'paid_at' => Carbon::now()->subDays(60 - ($n % 55)),
                'receipt_no' => sprintf('REC-%s-%04d', $edition->year ?: date('Y'), $n),
                'recorded_by' => $recorder?->id ?? 0,
                'meta' => [
                    'note' => 'Encaissement au secrétariat (scénario de démonstration).',
                    'recorded_by_name' => $recorder?->name ?? 'Secrétariat',
                ],
            ]
        );
    }

    /**
     * Séances d'accueil : une par atelier et par jour du colloque, pour disposer
     * d'assez de créneaux. Les six séances d'origine restent intactes.
     *
     * @param  Collection<int, Atelier>  $ateliers
     * @return Collection<int, EventSession>
     */
    private function buildSessions(Edition $edition, $ateliers)
    {
        $start = $edition->date_start ? Carbon::parse($edition->date_start) : Carbon::now()->startOfDay();
        $sessions = collect();

        foreach ($ateliers as $a => $atelier) {
            foreach (range(0, 3) as $day) {
                $begin = (clone $start)->addDays($day)->setTime(8 + ($a % 2) * 5, 30);

                $sessions->push(EventSession::updateOrCreate(
                    [
                        'edition_id' => $edition->id,
                        'atelier_id' => $atelier->id,
                        'label' => sprintf('%s - séance %d', Str::before($atelier->title, ' -'), $day + 1),
                    ],
                    [
                        'type' => 'atelier',
                        'session_day' => $begin->toDateString(),
                        'starts_at' => $begin,
                        'ends_at' => (clone $begin)->addHours(3),
                        'room' => 'Salle '.($a + 1),
                        'ordre' => $a * 10 + $day,
                    ]
                ));
            }
        }

        return $sessions;
    }

    /**
     * Place la communication sur le prochain créneau libre de la séance de son
     * atelier (30 min par communication, à la suite).
     *
     * @param  Collection<int, EventSession>  $sessions
     * @param  array<int, int>  $cursor
     */
    private function schedule(Edition $edition, $sessions, Participant $participant, Submission $submission, array &$cursor): void
    {
        if ($sessions->isEmpty()) {
            return;
        }

        $session = $sessions[$submission->id % $sessions->count()];
        $rank = $cursor[$session->id] ?? 0;
        $cursor[$session->id] = $rank + 1;

        $starts = $session->starts_at ? (clone $session->starts_at)->addMinutes(30 * $rank) : null;

        Slot::updateOrCreate(
            ['session_id' => $session->id, 'submission_id' => $submission->id],
            [
                'edition_id' => $edition->id,
                'session_label' => $session->label,
                'room' => $session->room,
                'starts_at' => $starts,
                'ends_at' => $starts ? (clone $starts)->addMinutes(30) : null,
                'participant_id' => $participant->id,
            ]
        );
    }

    /**
     * Relectures d'une communication en évaluation : deux relecteurs, dans des
     * états variés (invitée / acceptée / rendue, dont quelques retards) pour
     * éprouver le cycle de vie côté comité comme côté relecteur.
     */
    private function review(Edition $edition, Submission $submission, int $i): void
    {
        $reviewers = Participant::where('edition_id', $edition->id)->where('role', 'reviewer')->orderBy('id')->get();
        if ($reviewers->isEmpty()) {
            return;
        }

        foreach ([0, 1] as $k) {
            $reviewer = $reviewers[($i + $k) % $reviewers->count()];
            $state = ($i + $k) % 4;

            Review::updateOrCreate(
                ['submission_id' => $submission->id, 'reviewer_id' => $reviewer->id],
                match ($state) {
                    0 => [
                        'status' => 'invited',
                        'assigned_at' => Carbon::now()->subDays(10),
                        'due_at' => Carbon::now()->addDays(11),
                    ],
                    1 => [
                        'status' => 'accepted',
                        'assigned_at' => Carbon::now()->subDays(30),
                        'responded_at' => Carbon::now()->subDays(28),
                        // En retard : l'échéance est dépassée et rien n'est rendu.
                        'due_at' => Carbon::now()->subDays(9),
                    ],
                    2 => [
                        'status' => 'completed',
                        'assigned_at' => Carbon::now()->subDays(40),
                        'responded_at' => Carbon::now()->subDays(38),
                        'due_at' => Carbon::now()->subDays(19),
                        'recommendation' => 'minor',
                        'score' => 14.5,
                        'comments' => 'Communication solide ; préciser la méthodologie et actualiser la bibliographie.',
                        'comments_private' => 'Acceptable après révisions mineures.',
                    ],
                    default => [
                        'status' => 'declined',
                        'assigned_at' => Carbon::now()->subDays(25),
                        'responded_at' => Carbon::now()->subDays(24),
                        'decline_reason' => 'Hors de mon domaine d’expertise.',
                    ],
                }
            );
        }
    }

    private function title(int $n, string $track): string
    {
        $theme = trim(Str::after($track, '- ')) ?: 'sciences';
        $angles = [
            'Innovation et durabilité en %s : état des lieux',
            'Approches interdisciplinaires en %s',
            'Transition écologique et %s : quelles politiques ?',
            'Numérique, données et %s en Afrique de l’Ouest',
            'Formation, recherche et %s : le rôle des universités',
        ];

        return sprintf($angles[$n % count($angles)], mb_strtolower($theme)).' ('.$n.')';
    }

    private function abstract(string $track): string
    {
        return 'Cette communication interroge les leviers d’un développement durable dans le champ « '
            .$track.' ». Le contexte ouest-africain est posé, la méthodologie mobilisée est décrite, '
            .'puis les principaux résultats sont discutés au regard des politiques publiques et du rôle '
            .'des universités. Des pistes opérationnelles sont proposées pour les acteurs du secteur.';
    }
}
