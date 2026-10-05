<?php

use App\Meetings\MeetingProviderRegistry;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Slot;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\User;
use Database\Seeders\ColloqueAvenirSeeder;
use Database\Seeders\ColloqueAvenirVolumeSeeder;

/**
 * Le scénario « à l'échelle » du dernier colloque doit tenir ses promesses :
 * 200 communications et 93 % de paiements validés manuellement. Un seeder qui
 * dérive silencieusement fausse toutes les démonstrations et les captures.
 */
it('produit 200 communications dont 93 % payées manuellement', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    $submissions = Submission::where('edition_id', $edition->id)->where('ref_code', 'like', 'AVD-%')->get();

    expect($submissions)->toHaveCount(200);

    $paid = Payment::where('edition_id', $edition->id)
        ->whereIn('participant_id', $submissions->pluck('participant_id'))
        ->where('gateway', 'manual')
        ->where('status', 'paid')
        ->count();

    expect($paid)->toBe(186); // 93 % de 200
});

it('donne un jeu complet : statuts variés, créneaux et relectures', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    $byStatus = Submission::where('edition_id', $edition->id)->where('ref_code', 'like', 'AVD-%')
        ->get()->groupBy('status');

    // Toutes les étapes du circuit sont représentées.
    foreach (['submitted', 'under_review', 'revision', 'accepted', 'rejected'] as $status) {
        expect($byStatus->get($status))->not->toBeNull("statut manquant : {$status}");
    }

    // Les communications acceptées sont planifiées, et la relecture est en cours.
    expect($edition->slots()->where('submission_id', '>', 0)->count())
        ->toBeGreaterThanOrEqual($byStatus->get('accepted')->count());

    $reviews = Review::whereHas('submission', fn ($q) => $q->where('edition_id', $edition->id))->get();
    expect($reviews->where('status', 'completed')->count())->toBeGreaterThan(0);
    expect($reviews->filter(fn (Review $r) => $r->isLate())->count())->toBeGreaterThan(0);
});

/**
 * ⚠️ Les dates de l'événement sont une demande explicite, et tout le programme
 * s'y cale par décalage. Un seeder qui glisse d'un jour sort la clôture de
 * l'édition ou laisse un jour vide au milieu du colloque.
 */
it('tient le colloque du 5 au 9 octobre 2026, sans jour vide', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect($edition->date_start->toDateString())->toBe('2026-10-05');
    expect($edition->date_end->toDateString())->toBe('2026-10-09');

    $sessions = EventSession::where('edition_id', $edition->id)->whereNotNull('session_day')->get();

    // Aucune séance hors de la fenêtre annoncée...
    expect($sessions->filter(fn (EventSession $s) => $s->session_day->lt($edition->date_start)
        || $s->session_day->gt($edition->date_end)))->toBeEmpty();

    // ...et chaque jour du colloque porte quelque chose. Les séances calées sur
    // l'horloge (direct, replay) tombent le jour du lancement du seed : on ne
    // compte que les séances du programme, datées en dur.
    $jours = $sessions->map(fn (EventSession $s) => $s->session_day->toDateString())->unique();
    $vides = collect(range(0, 4))
        ->map(fn (int $i) => $edition->date_start->copy()->addDays($i)->toDateString())
        ->reject(fn (string $jour) => $jours->contains($jour))
        ->values()
        ->all();

    expect($vides)->toBe([]); // les jours sans aucune séance, s'il y en a

    // ⚠️ Le programme PUBLIÉ se cale sur la même fenêtre : replié de 7 à 5 jours,
    // il annonçait sinon une clôture le 11 octobre, deux jours après la fin.
    $jamais = collect($edition->settings['program_synthesis']['days'] ?? [])
        ->pluck('date')->unique()
        ->reject(fn (string $jour) => $jour >= $edition->date_start->toDateString()
            && $jour <= $edition->date_end->toDateString())
        ->values()->all();

    expect($jamais)->toBe([]); // les journées du programme hors de l'édition
});

/**
 * ⚠️ Une séance à trois communications ne démontre ni la modération, ni le
 * rapportage, ni le passage en salle : elle se vide avant d'avoir commencé. Le
 * nombre de blocs se déduit de ce minimum, et un bloc ajouté le casserait sans
 * bruit (130 communications sur 30 séances = 4,3 par séance).
 */
it('donne au moins 5 communications à chaque séance d’atelier', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    $seances = EventSession::where('edition_id', $edition->id)
        ->where('label', 'like', '% - séance %')->get();

    expect($seances)->not->toBeEmpty();

    $parSeance = $seances->map(fn (EventSession $s) => Slot::where('session_id', $s->id)
        ->where('submission_id', '>', 0)->count());

    expect($parSeance->min())->toBeGreaterThanOrEqual(5);

    // Et jamais plus que ce qu'un bloc de 3 h peut tenir (6 × 30 min) : une
    // séance surchargée déborderait sur la suivante.
    expect($parSeance->max())->toBeLessThanOrEqual(6);

    // Une communication se présente UNE fois : pas de créneau en double.
    $planifiees = Slot::where('edition_id', $edition->id)->where('submission_id', '>', 0)->pluck('submission_id');
    expect($planifiees->count())->toBe($planifiees->unique()->count());
});

/**
 * ⚠️ Deux blocs seulement ouvraient une salle : on ne pouvait pas vérifier la
 * visio depuis une communication prise au hasard du programme.
 */
it('ouvre la visioconférence sur TOUTES les séances', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    $sansVisio = EventSession::where('edition_id', $edition->id)->where('meeting_status', 'none')->get();

    expect($sansVisio->pluck('label')->all())->toBe([]);

    // Y compris les séances qui portent les communications.
    $seances = EventSession::where('edition_id', $edition->id)->where('label', 'like', '% - séance %')->get();
    expect($seances->every(fn (EventSession $s) => $s->hasMeeting()))->toBeTrue();
});

/**
 * ⚠️ 200 communications, un seul auteur chacune : rien n'éprouvait la liaison
 * auteurs ↔ comptes, « Mes communications » chez quelqu'un qui n'a rien déposé,
 * ni l'association différée d'un co-auteur non inscrit.
 */
it('co-signe les communications, avec des co-auteurs inscrits ET invités', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    $submissions = Submission::where('edition_id', $edition->id)->where('ref_code', 'like', 'AVD-%')->get();

    // Chaque communication porte un correspondant et au moins deux co-auteurs.
    foreach ($submissions->take(10) as $submission) {
        $auteurs = collect($submission->authors ?? []);
        expect($auteurs->where('corresponding', true))->toHaveCount(1);
        expect($auteurs->where('corresponding', false)->count())->toBeGreaterThanOrEqual(2);
    }

    $liens = SubmissionAuthor::where('edition_id', $edition->id)
        ->whereIn('submission_id', $submissions->pluck('id'))->get();

    expect($liens->where('role', 'corresponding'))->toHaveCount(200);
    expect($liens->where('role', 'coauthor')->count())->toBeGreaterThanOrEqual(400);

    // Les co-auteurs INSCRITS sont rattachés à leur compte : la communication
    // apparaît dans leur espace sans qu'ils l'aient déposée.
    $rattaches = $liens->where('role', 'coauthor')->where('status', 'linked');
    expect($rattaches)->not->toBeEmpty();
    expect($rattaches->every(fn (SubmissionAuthor $l) => filled($l->user_id)))->toBeTrue();

    // ...et les NON inscrits restent « invités », en attente de leur compte.
    $invites = $liens->where('status', 'invited');
    expect($invites)->not->toBeEmpty();
    expect($invites->every(fn (SubmissionAuthor $l) => $l->user_id === null && filled($l->email)))->toBeTrue();

    // Personne n'est co-auteur de sa propre communication.
    foreach ($submissions->take(20) as $submission) {
        $pour = $liens->where('submission_id', $submission->id);
        expect($pour->pluck('user_id')->filter()->duplicates())->toBeEmpty();
    }
});

/**
 * ⚠️ Une démo de visioconférence qui n'ouvre aucune salle ne démontre rien. Mais
 * le seeder ne doit PAS imposer de fournisseur : il suit `meetings.default`, et
 * le registre retombe seul sur Jitsi public quand ce défaut n'est pas configuré.
 * Forcer ici écraserait la configuration du site.
 */
it('programme des créneaux en visioconférence, sur des salles qui s’ouvrent', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    $visios = EventSession::where('edition_id', $edition->id)->where('meeting_status', '!=', 'none')->get();

    // Les trois états se voient sans attendre : direct, terminée (replay), programmée.
    foreach (['live', 'ended', 'scheduled'] as $etat) {
        expect($visios->where('meeting_status', $etat))->not->toBeEmpty("aucune visio « {$etat} »");
    }
    expect($visios->where('meeting_status', 'ended')->first()->recordingEmbedUrl())->not->toBeNull();

    // Aucun fournisseur imposé en base : c'est la configuration qui décide.
    expect($visios->filter(fn (EventSession $s) => filled($s->meeting_provider)))->toBeEmpty();

    // Et la salle s'ouvre quand même : le registre résout un fournisseur.
    expect(app(MeetingProviderRegistry::class)->for($visios->first())->isAvailable())->toBeTrue();

    // Une cérémonie est suivable sans être inscrit.
    expect($visios->where('type', 'plenary')->where('meeting_visibility', 'public'))->not->toBeEmpty();

    // Et surtout : des CRÉNEAUX de communication se tiennent en visio.
    $creneaux = $edition->slots()->where('submission_id', '>', 0)
        ->whereIn('session_id', $visios->pluck('id'))->count();
    expect($creneaux)->toBeGreaterThan(10);
});

it('rejoue sans rien dupliquer', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect(Submission::where('edition_id', $edition->id)->where('ref_code', 'like', 'AVD-%')->count())->toBe(200);
});

/**
 * ⚠️ `users.phone` est UNIQUE, et un compte semé peut avoir été renommé à la
 * main en base de développement : il garde son numéro, et l'insertion du compte
 * attendu échouait sur la contrainte. Tout le scénario étant dans une
 * transaction, c'étaient 200 communications qui ne se semaient PAS - pour un
 * numéro de téléphone fictif.
 */
it('se sème quand même si un numéro de téléphone est déjà pris', function () {
    $this->seed(ColloqueAvenirSeeder::class);

    User::create([
        'name' => 'Compte renommé à la main',
        'email' => 'quelqun@example.org',
        'password' => 'password',
        'phone' => '+2289100200', // celui que le scénario destine à auteur200
    ]);

    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();
    expect(Submission::where('edition_id', $edition->id)->where('ref_code', 'like', 'AVD-%')->count())->toBe(200);

    // Le compte attendu existe, simplement sans numéro.
    $auteur = User::where('email', 'auteur200@avenir-durable.tg')->first();
    expect($auteur)->not->toBeNull()
        ->and($auteur->phone)->toBeNull();
});

/**
 * Le but des co-auteurs n'est pas la ligne en base : c'est que la communication
 * APPARAISSE chez quelqu'un qui ne l'a pas déposée. « Mes communications » lit
 * les liaisons confirmées (`authorLinks`, status « linked ») - des liens semés
 * sous une autre forme ne montreraient rien.
 */
it('fait apparaître la communication chez ses co-auteurs', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $compte = User::where('email', 'comm2@test.tg')->firstOrFail();

    // Un dépôt à soi, deux co-signatures : tout compte de démonstration en a.
    $sien = Submission::whereHas('participant', fn ($p) => $p->where('user_id', $compte->id))->count();
    $cosignees = SubmissionAuthor::where('user_id', $compte->id)
        ->where('role', 'coauthor')->where('status', 'linked')->count();

    expect($sien)->toBe(1)->and($cosignees)->toBe(2);

    $this->actingAs($compte, 'account')
        ->get(route('account.submissions.index'))
        ->assertOk()
        ->assertSee('Communication test 2', false)  // la sienne
        ->assertSee('Communication test 1', false)  // co-signée
        ->assertSee('Communication test 6', false); // co-signée
});
