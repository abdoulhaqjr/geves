<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Participant;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
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
 * conjoint Parakou-Kara (cf. {@see ColloqueAvenirSeeder}, qui crée l'événement,
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

    /**
     * Communications attendues au MINIMUM dans chaque séance d'atelier.
     *
     * ⚠️ C'est ce nombre qui dicte le nombre de blocs, pas l'inverse : une séance
     * de 3 h tient 6 communications de 30 min, et 130 communications acceptées
     * réparties sur plus de 26 séances en laisseraient à 3 ou 4. Une séance à
     * trois communications ne démontre ni la modération, ni le rapportage, ni le
     * passage en salle - elle se vide avant d'avoir commencé.
     */
    private const MIN_PAR_SEANCE = 5;

    /** Co-auteurs inscrits ajoutés à chaque communication. */
    private const COAUTEURS = [37, 83];

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
        $event = Event::where('slug', 'avenir-durable-2025')->first()
            ?? Event::whereHas('colloque', fn ($q) => $q->where('slug', 'parakou-kara'))->latest('id')->first();

        if (! $event) {
            $this->command?->error('Événement introuvable : lancez d’abord ColloqueAvenirSeeder.');

            return;
        }

        $ateliers = $event->ateliers()->orderBy('ordre')->get();
        if ($ateliers->isEmpty()) {
            $this->command?->error('Aucun atelier sur l’événement : lancez d’abord ColloqueAvenirSeeder.');

            return;
        }

        $tracks = collect($event->settings['tracks'] ?? [])->values();
        $grades = ParticipantTypes::all();
        $recorder = User::where('email', 'admin@univkara.tg')->first();

        // Séances d'accueil des communications : une par atelier et par jour, afin
        // d'avoir assez de créneaux pour placer les communications acceptées.
        $sessions = $this->buildSessions($event, $ateliers);

        // Tirage reproductible : relancer le seeder donne exactement le même jeu.
        mt_srand(20260928);

        $statuses = $this->statusDeck();
        $paidFlags = $this->paidDeck();

        $slotCursor = [];
        $created = 0;
        // Rang de planification : il tourne sur les séances pour que la charge se
        // répartisse à l'unité près (cf. schedule()).
        $place = 0;
        /** @var array<int, array<string, mixed>> $roster */
        $roster = [];

        DB::transaction(function () use (
            $event, $tracks, $grades, $recorder, $sessions,
            $statuses, $paidFlags, &$slotCursor, &$created, &$place, &$roster
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

                // ⚠️ `users.phone` est UNIQUE, et un compte semé peut avoir été
                // renommé à la main en base de développement : il garde alors ce
                // numéro, et l'insertion du compte attendu échoue sur la
                // contrainte. Tout le scénario étant dans une transaction, c'est
                // 200 communications qui ne seraient PAS semées, pour un numéro de
                // téléphone fictif. On sème donc le compte sans numéro.
                $phone = sprintf('+2289%06d', 100000 + $n);
                $phonePris = User::where('phone', $phone)->where('email', '!=', $email)->exists();

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
                        'phone' => $phonePris ? null : $phone,
                    ]
                );

                $participant = Participant::updateOrCreate(
                    ['event_id' => $event->id, 'user_id' => $user->id],
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
                    ['event_id' => $event->id, 'ref_code' => sprintf('AVD-%03d', $n)],
                    [
                        'participant_id' => $participant->id,
                        'access_token' => Str::random(40),
                        'title' => $this->title($n, $track),
                        'authors' => [['name' => $this->authorName($first, $last), 'email' => $email, 'affiliation' => $institution, 'corresponding' => true]],
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
                $roster[$i] = [
                    'user' => $user, 'submission' => $submission,
                    'name' => $this->authorName($first, $last), 'email' => $email, 'affiliation' => $institution,
                ];

                if ($isPaid) {
                    $this->payManually($event, $participant, $recorder, $n);
                }

                // Communication acceptée : on lui réserve un créneau.
                if ($status === 'accepted') {
                    $this->schedule($event, $sessions, $participant, $submission, $slotCursor, $place);
                }

                // Communications en cours d'évaluation : relectures confiées.
                if (in_array($status, ['under_review', 'revision'], true)) {
                    $this->review($event, $submission, $i);
                }
            }
        });

        // Co-auteurs : en SECONDE PASSE, une fois les 200 comptes créés - une
        // communication ne peut citer que des gens qui existent déjà.
        $liens = $this->attachCoauthors($event, $roster);

        // ⚠️ On compte les paiements DU SCÉNARIO : l'événement en portait déjà
        // quelques-uns (cf. ColloqueAvenirSeeder), et les additionner donnerait
        // un taux faussé.
        $participantIds = Submission::where('event_id', $event->id)
            ->where('ref_code', 'like', 'AVD-%')->pluck('participant_id');
        $paid = Payment::where('event_id', $event->id)
            ->whereIn('participant_id', $participantIds)
            ->where('gateway', 'manual')->where('status', 'paid')->count();

        $this->command?->info(sprintf(
            '%d communications sur « %s » - %d paiements validés manuellement (%.1f %%) - %d liens d’auteurs.',
            $created,
            $event->title,
            $paid,
            $created > 0 ? $paid / $created * 100 : 0,
            $liens,
        ));

        // ⚠️ On DIT ce qu'on a produit : la règle « %d communications minimum par
        // séance » est une demande explicite, et un plan qui dérive doit se voir
        // au moment du seed, pas trois écrans plus loin.
        $parSeance = $sessions->map(fn (EventSession $seance) => Slot::where('session_id', $seance->id)
            ->where('submission_id', '>', 0)->count());

        $this->command?->info(sprintf(
            '%d séances d’atelier, toutes en visioconférence - %d à %d communications par séance (minimum visé : %d).',
            $sessions->count(),
            $parSeance->min() ?? 0,
            $parSeance->max() ?? 0,
            self::MIN_PAR_SEANCE,
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
    private function payManually(Event $event, Participant $participant, ?User $recorder, int $n): void
    {
        $methods = array_keys(Payment::MANUAL_METHODS);

        Payment::updateOrCreate(
            ['event_id' => $event->id, 'participant_id' => $participant->id],
            [
                'amount' => Fees::resolveAmount($event, $participant->title) ?? 0,
                'currency' => 'XOF',
                'method' => $methods[$n % count($methods)],
                'gateway' => 'manual',
                'status' => 'paid',
                'paid_at' => Carbon::now()->subDays(60 - ($n % 55)),
                'receipt_no' => sprintf('REC-%s-%04d', $event->year ?: date('Y'), $n),
                'recorded_by' => $recorder?->id ?? 0,
                'meta' => [
                    'note' => 'Encaissement au secrétariat (scénario de démonstration).',
                    'recorded_by_name' => $recorder?->name ?? 'Secrétariat',
                ],
            ]
        );
    }

    /**
     * Séances d'accueil des communications : 6 ateliers (une salle chacun, en
     * parallèle) sur une série de blocs matin/après-midi. Les blocs sont calés
     * HORS des fenêtres des plénières (cf. {@see ColloqueAvenirSeeder::buildPlenaries()} :
     * inauguration lun. 12/10 matin, table ronde mer. 14/10 matin, formation
     * jeu. 15/10 après-midi, clôture ven. 16/10 matin), afin que l'exclusivité des
     * plénières soit respectée et qu'aucun conflit de salle n'apparaisse.
     *
     * ⚠️ **Le NOMBRE de blocs se déduit du minimum par séance, pas du calendrier.**
     * 130 communications acceptées sur 7 blocs × 6 ateliers = 42 séances en
     * laissaient 3 par séance : une séance à trois communications ne démontre ni
     * la modération, ni le rapportage, ni le passage en salle. À 4 blocs × 6 =
     * **24 séances**, chacune en porte 5 ou 6 - et 6 × 30 min remplit exactement
     * le bloc de 3 h. Ajouter un cinquième bloc redescendrait à 4 par séance.
     *
     * ⚠️ **Toutes les séances ont la visioconférence** (`scheduled`) : une
     * événement où seuls deux blocs l'ouvraient ne permettait pas de vérifier la
     * salle depuis n'importe quelle communication du programme.
     *
     * @param  Collection<int, Atelier>  $ateliers
     * @return Collection<int, EventSession>
     */
    private function buildSessions(Event $event, $ateliers)
    {
        $start = $event->date_start ? Carbon::parse($event->date_start) : Carbon::now()->startOfDay();
        $sessions = collect();

        // [décalage en jours, h début, min début, h fin, min fin]
        // ⚠️ Un bloc par jour : l'événement court du 12 au 16 octobre, et un jour sans
        // rien au milieu du programme se lit comme un trou de données. Le dernier
        // jour (J+4) n'en porte pas : c'est la matinée de clôture, et la plénière
        // l'occupe - aucun jour ne reste vide pour autant.
        // ⚠️ **J+1 se tient l'APRÈS-MIDI** : il faut des créneaux programmés dans
        // au moins deux ateliers EN PARALLÈLE le mardi 13/10 entre 14h et 18h. Les
        // six ateliers y tiennent leur séance 2 de 14:00 à 17:00, dans six salles
        // distinctes ; la matinée de ce jour-là ne porte plus que les séances de
        // 11:30-12:30 du seeder de base. ⚠️ Le programme synthétique publié annonce
        // la même heure (cf. ColloqueAvenirSeeder) : le déplacer ici seul ferait
        // mentir la page du colloque.
        $blocks = [
            [0, 14, 0, 17, 0],  // lun. 12/10 : après-midi (matin = séance inaugurale)
            [1, 14, 0, 17, 0],  // mar. 13/10 : après-midi (six ateliers en parallèle)
            [2, 14, 0, 17, 0],  // mer. 14/10 : après-midi (matin = table ronde)
            [3, 8, 30, 11, 30], // jeu. 15/10 : matin (après-midi = formation)
        ];

        $equipes = $this->equipesDeSeance();

        foreach ($ateliers as $a => $atelier) {
            $mod = $equipes[$a]['mod'] ?? null;
            $rap = $equipes[$a]['rap'] ?? null;

            foreach ($blocks as $b => [$day, $sh, $sm, $eh, $em]) {
                $begin = (clone $start)->addDays($day)->setTime($sh, $sm);
                $finish = (clone $start)->addDays($day)->setTime($eh, $em);

                $sessions->push(EventSession::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'atelier_id' => $atelier->id,
                        'label' => sprintf('%s - séance %d', Str::before($atelier->title, ' -'), $b + 1),
                    ],
                    [
                        'type' => 'atelier',
                        'session_day' => $begin->toDateString(),
                        'starts_at' => $begin,
                        'ends_at' => $finish,
                        'room' => 'Salle '.($a + 1),
                        'ordre' => $a * 10 + $b,
                        // ⚠️ **Toutes les séances ouvrent une salle.** Deux blocs
                        // seulement la portaient : on ne pouvait pas vérifier la visio
                        // depuis une communication prise au hasard du programme.
                        // ⚠️ Fournisseur NON forcé : il suit `meetings.default`.
                        'meeting_provider' => null,
                        'meeting_status' => 'scheduled',
                        'meeting_visibility' => 'participants',
                        // ⚠️ **Modérateur et rapporteur se rattachent PAR E-MAIL** :
                        // c'est l'adresse qui ouvre « Séances à modérer » et « Séances
                        // à rapporter » dans l'espace de la personne. Un nom seul ne
                        // lie personne (cf. CLAUDE.md, module Programme).
                        'moderator_name' => $mod?->name,
                        'moderator_email' => $mod?->email,
                        'rapporteur_name' => $rap?->name,
                        'rapporteur_email' => $rap?->email,
                    ]
                ));
            }
        }

        // ⚠️ **Le plan peut RÉTRÉCIR** (il est passé de 7 blocs à 4). `updateOrCreate`
        // ne sait que créer ou mettre à jour : rejoué sur une base déjà semée, il
        // laisserait en place les séances des blocs disparus. Les communications
        // resteraient étalées sur 42 séances, la règle « 5 au minimum » serait
        // fausse, et rien ne le signalerait. On retire donc les séances d'atelier
        // de CE seeder qui ne sont plus au plan, et leurs créneaux avec elles -
        // `slots.session_id` n'a pas de clé étrangère, ils survivraient orphelins.
        $perimees = EventSession::where('event_id', $event->id)
            ->where('label', 'like', '% - séance %')
            ->whereNotIn('id', $sessions->pluck('id'))
            ->pluck('id');

        if ($perimees->isNotEmpty()) {
            Slot::whereIn('session_id', $perimees)->delete();
            EventSession::whereIn('id', $perimees)->delete();
        }

        return $sessions;
    }

    /**
     * Équipes de séance, confiées aux comptes de démonstration.
     *
     * ⚠️ **DEUX ateliers seulement, et c'est une contrainte d'emploi du temps,
     * pas une économie.** Les six ateliers se tiennent EN PARALLÈLE : confier
     * leurs séances aux deux mêmes comptes ferait de chacun le modérateur de
     * trois salles à la même heure - le genre de programme faux que le reste du
     * jeu de démonstration s'applique à éviter. Les ateliers 1 et 2 sont donc
     * fonctionnels **de bout en bout** - espace « Séances à modérer », présence,
     * validation du passage, rapport de séance - sur leurs QUATRE blocs, qui ne
     * se chevauchent pas entre eux. Les quatre autres montrent la charge
     * parallèle.
     *
     * ⚠️ Ces comptes portent déjà les séances de 11:30-12:30 du seeder de base
     * (ColloqueAvenirSeeder) : aucun bloc d'atelier ne les recouvre.
     *
     * Absents de la base, on ne confie RIEN plutôt que d'écrire une adresse qui
     * n'ouvre aucun espace.
     *
     * @return array<int, array{mod: ?User, rap: ?User}>
     */
    private function equipesDeSeance(): array
    {
        $compte = fn (string $email): ?User => User::where('email', $email)->first();

        return [
            0 => ['mod' => $compte('moderateur1@test.tg'), 'rap' => $compte('rapporteur1@test.tg')],
            1 => ['mod' => $compte('moderateur2@test.tg'), 'rap' => $compte('rapporteur2@test.tg')],
        ];
    }

    /**
     * **Co-auteurs des communications.**
     *
     * ⚠️ **Le scénario n'en portait AUCUN** : 200 communications, un seul auteur
     * chacune. Rien n'éprouvait la liaison auteurs ↔ comptes, « Mes
     * communications » chez quelqu'un qui n'a rien déposé, les plafonds de
     * co-auteurs attestés (`coauthors_max`), ni la porte d'entrée des co-auteurs.
     * Une communication scientifique sans co-auteur est l'exception, pas la règle.
     *
     * ⚠️ **On n'appelle PAS `LinkSubmissionAuthors`** : cette action NOTIFIE - un
     * e-mail et un SMS par co-auteur rapproché. Six cents envois pour un seed. Un
     * seeder pose l'ÉTAT FINAL, il ne rejoue pas le processus ; les lignes
     * produites ici ont exactement la forme que l'action leur aurait donnée.
     *
     * Deux co-auteurs INSCRITS (pris parmi les autres auteurs du scénario) et,
     * une communication sur trois, un co-auteur NON inscrit resté « invité » :
     * c'est lui qui donne de la matière à l'association différée
     * (`LinkPendingAuthorships`, au moment où la personne crée son compte).
     *
     * @param  array<int, array<string, mixed>>  $roster
     */
    private function attachCoauthors(Event $event, array $roster): int
    {
        $total = count($roster);

        if ($total === 0) {
            return 0;
        }

        $liens = 0;

        foreach ($roster as $i => $ligne) {
            /** @var Submission $submission */
            $submission = $ligne['submission'];

            $auteurs = [[
                'name' => $ligne['name'],
                'email' => $ligne['email'],
                'affiliation' => $ligne['affiliation'],
                'corresponding' => true,
            ]];
            $etats = [['role' => 'corresponding', 'user' => $ligne['user']->id, 'match' => 'self', 'status' => 'linked']];

            // ⚠️ 37 et 83 sont premiers avec 200 : chaque auteur se retrouve
            // co-auteur de deux AUTRES communications, sans grappe ni doublon, et
            // jamais co-auteur de la sienne.
            foreach (self::COAUTEURS as $pas) {
                $co = $roster[($i + $pas) % $total];
                $auteurs[] = [
                    'name' => $co['name'],
                    'email' => $co['email'],
                    'affiliation' => $co['affiliation'],
                    'corresponding' => false,
                ];
                $etats[] = ['role' => 'coauthor', 'user' => $co['user']->id, 'match' => 'email', 'status' => 'linked'];
            }

            // Une communication sur trois cite quelqu'un qui n'a pas de compte.
            if (($i + 1) % 3 === 0) {
                $auteurs[] = [
                    'name' => $this->authorName(
                        self::FIRST_NAMES[($i + 5) % count(self::FIRST_NAMES)],
                        self::LAST_NAMES[($i + 3) % count(self::LAST_NAMES)],
                    ),
                    'email' => sprintf('coauteur-externe%03d@example.org', $i + 1),
                    'affiliation' => 'Laboratoire associé',
                    'corresponding' => false,
                ];
                $etats[] = ['role' => 'coauthor', 'user' => null, 'match' => null, 'status' => 'invited'];
            }

            $submission->update(['authors' => $auteurs]);

            foreach ($etats as $index => $etat) {
                SubmissionAuthor::updateOrCreate(
                    ['submission_id' => $submission->id, 'author_index' => $index],
                    [
                        'event_id' => $event->id,
                        'user_id' => $etat['user'],
                        'role' => $etat['role'],
                        'name' => $auteurs[$index]['name'],
                        'email' => $auteurs[$index]['email'],
                        'affiliation' => $auteurs[$index]['affiliation'],
                        'match_type' => $etat['match'],
                        'status' => $etat['status'],
                        'notified_at' => $etat['status'] === 'linked' && $etat['match'] !== 'self' ? now() : null,
                        'invited_at' => $etat['status'] === 'invited' ? now() : null,
                        // ⚠️ Jeton DÉTERMINISTE : il porte le lien de déclinaison, et
                        // un `Str::random()` le changerait à chaque rejeu - les liens
                        // déjà envoyés en démonstration tomberaient en 404.
                        'token' => substr(hash('sha256', 'avd-auteur:'.$submission->id.':'.$index), 0, 48),
                    ]
                );
                $liens++;
            }
        }

        return $liens;
    }

    /**
     * Place la communication sur le prochain créneau libre de la séance suivante
     * (30 min par communication, à la suite).
     *
     * ⚠️ **La séance se choisit au TOUR DE RÔLE, pas par `id % n`.** Le modulo de
     * l'identifiant répartissait les communications ACCEPTÉES - une sur trois,
     * aux identifiants non contigus - et laissait des séances à 3 quand d'autres
     * en avaient 8. Un rang qui tourne garantit l'écart d'une unité au plus entre
     * la séance la plus chargée et la moins chargée : c'est ce qui fait tenir
     * `MIN_PAR_SEANCE`.
     *
     * ⚠️ **Le créneau est identifié par sa COMMUNICATION**, pas par le couple
     * séance + communication : une communication se présente une fois. Avec
     * l'ancienne clé, un plan modifié créait un SECOND créneau au lieu de
     * déplacer le premier, et la communication apparaissait dans deux séances.
     *
     * @param  Collection<int, EventSession>  $sessions
     * @param  array<int, int>  $cursor
     */
    private function schedule(Event $event, $sessions, Participant $participant, Submission $submission, array &$cursor, int &$place): void
    {
        if ($sessions->isEmpty()) {
            return;
        }

        $session = $sessions[$place % $sessions->count()];
        $place++;
        $rank = $cursor[$session->id] ?? 0;
        $cursor[$session->id] = $rank + 1;

        $starts = $session->starts_at ? (clone $session->starts_at)->addMinutes(30 * $rank) : null;

        Slot::updateOrCreate(
            ['event_id' => $event->id, 'submission_id' => $submission->id],
            [
                'session_id' => $session->id,
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
    private function review(Event $event, Submission $submission, int $i): void
    {
        $reviewers = Participant::where('event_id', $event->id)->where('role', 'reviewer')->orderBy('id')->get();
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

    /**
     * Nom d'auteur au format du livre de référence : « NOM Prénom » - nom de famille
     * en CAPITALES, prénom en casse normale (c'est ce qu'affiche le livret, pas tout
     * en majuscules). Le nom du COMPTE reste « Prénom Nom ».
     */
    private function authorName(string $first, string $last): string
    {
        return trim(mb_strtoupper(trim($last), 'UTF-8').' '.trim($first));
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

        return sprintf($angles[$n % count($angles)], mb_strtolower($theme));
    }

    private function abstract(string $track): string
    {
        return 'Cette communication interroge les leviers d’un développement durable dans le champ « '
            .$track.' ». Le contexte ouest-africain est posé, la méthodologie mobilisée est décrite, '
            .'puis les principaux résultats sont discutés au regard des politiques publiques et du rôle '
            .'des universités. Des pistes opérationnelles sont proposées pour les acteurs du secteur.';
    }
}
