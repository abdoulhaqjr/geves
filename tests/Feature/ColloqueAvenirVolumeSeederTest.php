<?php

use App\Meetings\MeetingProviderRegistry;
use App\Models\Edition;
use App\Models\EventSession;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Submission;
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
it('tient le colloque du 2 au 8 octobre 2026, sans jour vide', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect($edition->date_start->toDateString())->toBe('2026-10-02');
    expect($edition->date_end->toDateString())->toBe('2026-10-08');

    $sessions = EventSession::where('edition_id', $edition->id)->whereNotNull('session_day')->get();

    // Aucune séance hors de la fenêtre annoncée...
    expect($sessions->filter(fn (EventSession $s) => $s->session_day->lt($edition->date_start)
        || $s->session_day->gt($edition->date_end)))->toBeEmpty();

    // ...et chaque jour du colloque porte quelque chose. Les séances calées sur
    // l'horloge (direct, replay) tombent le jour du lancement du seed : on ne
    // compte que les séances du programme, datées en dur.
    $jours = $sessions->map(fn (EventSession $s) => $s->session_day->toDateString())->unique();
    $vides = collect(range(0, 6))
        ->map(fn (int $i) => $edition->date_start->copy()->addDays($i)->toDateString())
        ->reject(fn (string $jour) => $jours->contains($jour))
        ->values()
        ->all();

    expect($vides)->toBe([]); // les jours sans aucune séance, s'il y en a
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
