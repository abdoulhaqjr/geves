<?php

use App\Enums\MeetingStatus;
use App\Meetings\MeetingProviderRegistry;
use App\Models\Event;
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

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $submissions = Submission::where('event_id', $event->id)->where('ref_code', 'like', 'AVD-%')->get();

    expect($submissions)->toHaveCount(200);

    $paid = Payment::where('event_id', $event->id)
        ->whereIn('participant_id', $submissions->pluck('participant_id'))
        ->where('gateway', 'manual')
        ->where('status', 'paid')
        ->count();

    expect($paid)->toBe(186); // 93 % de 200
});

it('donne un jeu complet : statuts variés, créneaux et relectures', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $byStatus = Submission::where('event_id', $event->id)->where('ref_code', 'like', 'AVD-%')
        ->get()->groupBy('status');

    // Toutes les étapes du circuit sont représentées.
    foreach (['submitted', 'under_review', 'revision', 'accepted', 'rejected'] as $status) {
        expect($byStatus->get($status))->not->toBeNull("statut manquant : {$status}");
    }

    // Les communications acceptées sont planifiées, et la relecture est en cours.
    expect($event->slots()->where('submission_id', '>', 0)->count())
        ->toBeGreaterThanOrEqual($byStatus->get('accepted')->count());

    $reviews = Review::whereHas('submission', fn ($q) => $q->where('event_id', $event->id))->get();
    expect($reviews->where('status', 'completed')->count())->toBeGreaterThan(0);
    expect($reviews->filter(fn (Review $r) => $r->isLate())->count())->toBeGreaterThan(0);
});

/**
 * ⚠️ Les dates de l'événement sont une demande explicite, et tout le programme
 * s'y cale par décalage. Un seeder qui glisse d'un jour sort la clôture de
 * l'événement ou laisse un jour vide au milieu du colloque.
 */
it('tient le colloque du 12 au 16 octobre 2026, sans jour vide', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect($event->date_start->toDateString())->toBe('2026-10-12');
    expect($event->date_end->toDateString())->toBe('2026-10-16');

    $sessions = EventSession::where('event_id', $event->id)->whereNotNull('session_day')->get();

    // ⚠️ **Deux séances de démonstration sont calées sur l'HORLOGE** - une EN
    // DIRECT, une TERMINÉE avec replay - et non sur les dates du colloque : une
    // séance « terminée » posée à une date fixe s'annoncerait terminée avant
    // d'avoir eu lieu. Elles tombent donc le jour du seed, qui n'est dans la
    // fenêtre que tant que le colloque n'est pas passé - il l'est depuis le
    // 17 octobre 2026. Ce cas porte sur le PROGRAMME ; les écarter n'affaiblit
    // rien, c'est le programme qui se cale sur `$start`.
    $surHorloge = fn (EventSession $s) => in_array(
        $s->meetingStatusEnum(), [MeetingStatus::Live, MeetingStatus::Ended], true,
    );
    $programme = $sessions->reject($surHorloge);

    // ⚠️ L'exception vaut pour DEUX séances, pas pour une catégorie ouverte :
    // sans ce compte, une vraie séance hors fenêtre se cacherait derrière elle
    // le jour où quelqu'un marquerait tout un bloc « terminé ».
    expect($sessions->filter($surHorloge))->toHaveCount(2);

    // Aucune séance du programme hors de la fenêtre annoncée...
    expect($programme->filter(fn (EventSession $s) => $s->session_day->lt($event->date_start)
        || $s->session_day->gt($event->date_end)))->toBeEmpty();

    // ...et chaque jour du colloque porte quelque chose. ⚠️ Compté sur le seul
    // programme, lui aussi : une séance d'horloge qui tomberait un jour du
    // colloque masquerait le trou qu'on cherche ici.
    $jours = $programme->map(fn (EventSession $s) => $s->session_day->toDateString())->unique();
    $vides = collect(range(0, 4))
        ->map(fn (int $i) => $event->date_start->copy()->addDays($i)->toDateString())
        ->reject(fn (string $jour) => $jours->contains($jour))
        ->values()
        ->all();

    expect($vides)->toBe([]); // les jours sans aucune séance, s'il y en a

    // ⚠️ Le programme PUBLIÉ se cale sur la même fenêtre : replié de 7 à 5 jours,
    // il annonçait sinon une clôture le 11 octobre, deux jours après la fin.
    $jamais = collect($event->settings['program_synthesis']['days'] ?? [])
        ->pluck('date')->unique()
        ->reject(fn (string $jour) => $jour >= $event->date_start->toDateString()
            && $jour <= $event->date_end->toDateString())
        ->values()->all();

    expect($jamais)->toBe([]); // les journées du programme hors de l'événement
});

/**
 * ⚠️ **Le mardi 13/10, de 14h à 18h, au moins DEUX ateliers doivent tourner en
 * parallèle, avec des communications.** C'est une demande explicite : c'est le
 * créneau sur lequel on vérifie que deux salles simultanées fonctionnent
 * vraiment (visio, modération, passage en salle) sans se marcher dessus.
 *
 * Le cas éprouve la SIMULTANÉITÉ, pas le nombre de séances du jour : deux
 * séances d'ateliers différents à des heures qui ne se recouvrent pas ne
 * démontreraient rien. Il regroupe donc les séances par horaire identique et
 * exige qu'un même horaire en porte au moins deux, dans des SALLES distinctes -
 * deux séances dans la même salle au même moment ne sont pas parallèles, elles
 * sont en conflit.
 *
 * ⚠️ Et il exige que le programme PUBLIÉ annonce la même heure : les séances
 * vivent dans `event_sessions`, la page du colloque lit
 * `settings.program_synthesis`, et rien n'oblige structurellement les deux à
 * rester d'accord. Déplacer le bloc sans déplacer le programme ferait annoncer
 * des communications là où aucune salle n'ouvre.
 */
it('fait tourner au moins deux ateliers en parallèle le 13/10 de 14h à 18h', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $jour = $event->date_start->copy()->addDay();

    expect($jour->toDateString())->toBe('2026-10-13');

    $fenetre = EventSession::where('event_id', $event->id)
        ->where('type', 'atelier')
        ->whereDate('session_day', $jour->toDateString())
        ->get()
        ->filter(fn (EventSession $s) => $s->starts_at !== null
            && $s->ends_at !== null
            && $s->starts_at->format('H:i') >= '14:00'
            && $s->ends_at->format('H:i') <= '18:00');

    expect($fenetre)->not->toBeEmpty();

    // Des ateliers SIMULTANÉS : un même horaire, plusieurs séances.
    $parHoraire = $fenetre->groupBy(fn (EventSession $s) => $s->starts_at->format('H:i').'-'.$s->ends_at->format('H:i'));
    $simultanees = $parHoraire->filter(fn ($groupe) => $groupe->count() >= 2);

    expect($simultanees)->not->toBeEmpty();

    foreach ($simultanees as $horaire => $groupe) {
        // Des ateliers différents, dans des salles différentes : parallèles, et
        // non en conflit sur la même salle.
        expect($groupe->pluck('atelier_id')->filter()->unique()->count())->toBe($groupe->count(), $horaire);
        expect($groupe->pluck('room')->unique()->count())->toBe($groupe->count(), $horaire);

        foreach ($groupe as $seance) {
            // Avec des communications, et une salle qui s'ouvre : une séance vide
            // ou sans visio ne démontre rien de ce qu'on vient voir sur ce créneau.
            expect(Slot::where('session_id', $seance->id)->where('submission_id', '>', 0)->count())
                ->toBeGreaterThan(0, $seance->label);
            expect($seance->hasMeeting())->toBeTrue($seance->label);
        }
    }

    // ⚠️ **« Fonctionnels » va plus loin que « programmés »** : au moins deux des
    // séances de la fenêtre sont CONFIÉES - modérateur ET rapporteur rattachés par
    // e-mail à un compte existant -, sans quoi ni l'espace « Séances à modérer »,
    // ni la présence, ni la validation du passage, ni le rapport de séance ne
    // s'ouvrent à personne.
    $confiees = $fenetre->filter(fn (EventSession $s) => filled($s->moderator_email)
        && filled($s->rapporteur_email)
        && User::where('email', $s->moderator_email)->exists()
        && User::where('email', $s->rapporteur_email)->exists());

    expect($confiees->count())->toBeGreaterThanOrEqual(2);

    // ⚠️ Et jamais la même personne dans deux salles à la même heure : un
    // modérateur ubiquitaire rendrait ces séances fausses au lieu de fonctionnelles.
    foreach ($confiees->groupBy(fn (EventSession $s) => $s->starts_at->format('H:i')) as $heure => $groupe) {
        expect($groupe->pluck('moderator_email')->unique()->count())->toBe($groupe->count(), (string) $heure);
        expect($groupe->pluck('rapporteur_email')->unique()->count())->toBe($groupe->count(), (string) $heure);
    }

    // Le programme publié annonce bien des ateliers sur ce créneau, ce jour-là.
    $annonce = collect($event->settings['program_synthesis']['days'] ?? [])
        ->where('date', $jour->toDateString())
        ->flatMap(fn (array $j) => $j['rows'] ?? [])
        ->filter(fn (array $r) => ($r['type'] ?? '') === 'atelier'
            && ($r['start'] ?? '') >= '14:00'
            && ($r['end'] ?? '') <= '18:00');

    expect($annonce)->not->toBeEmpty();
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

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $seances = EventSession::where('event_id', $event->id)
        ->where('label', 'like', '% - séance %')->get();

    expect($seances)->not->toBeEmpty();

    $parSeance = $seances->map(fn (EventSession $s) => Slot::where('session_id', $s->id)
        ->where('submission_id', '>', 0)->count());

    expect($parSeance->min())->toBeGreaterThanOrEqual(5);

    // Et jamais plus que ce qu'un bloc de 3 h peut tenir (6 × 30 min) : une
    // séance surchargée déborderait sur la suivante.
    expect($parSeance->max())->toBeLessThanOrEqual(6);

    // Une communication se présente UNE fois : pas de créneau en double.
    $planifiees = Slot::where('event_id', $event->id)->where('submission_id', '>', 0)->pluck('submission_id');
    expect($planifiees->count())->toBe($planifiees->unique()->count());
});

/**
 * ⚠️ Deux blocs seulement ouvraient une salle : on ne pouvait pas vérifier la
 * visio depuis une communication prise au hasard du programme.
 */
it('ouvre la visioconférence sur TOUTES les séances', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $sansVisio = EventSession::where('event_id', $event->id)->where('meeting_status', 'none')->get();

    expect($sansVisio->pluck('label')->all())->toBe([]);

    // Y compris les séances qui portent les communications.
    $seances = EventSession::where('event_id', $event->id)->where('label', 'like', '% - séance %')->get();
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

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $submissions = Submission::where('event_id', $event->id)->where('ref_code', 'like', 'AVD-%')->get();

    // Chaque communication porte un correspondant et au moins deux co-auteurs.
    foreach ($submissions->take(10) as $submission) {
        $auteurs = collect($submission->authors ?? []);
        expect($auteurs->where('corresponding', true))->toHaveCount(1);
        expect($auteurs->where('corresponding', false)->count())->toBeGreaterThanOrEqual(2);
    }

    $liens = SubmissionAuthor::where('event_id', $event->id)
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

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    $visios = EventSession::where('event_id', $event->id)->where('meeting_status', '!=', 'none')->get();

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
    $creneaux = $event->slots()->where('submission_id', '>', 0)
        ->whereIn('session_id', $visios->pluck('id'))->count();
    expect($creneaux)->toBeGreaterThan(10);
});

it('rejoue sans rien dupliquer', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect(Submission::where('event_id', $event->id)->where('ref_code', 'like', 'AVD-%')->count())->toBe(200);
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

    $event = Event::where('slug', 'avenir-durable-2025')->firstOrFail();
    expect(Submission::where('event_id', $event->id)->where('ref_code', 'like', 'AVD-%')->count())->toBe(200);

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
