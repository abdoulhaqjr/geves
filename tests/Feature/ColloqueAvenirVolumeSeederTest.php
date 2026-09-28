<?php

use App\Models\Edition;
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

it('rejoue sans rien dupliquer', function () {
    $this->seed(ColloqueAvenirSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);
    $this->seed(ColloqueAvenirVolumeSeeder::class);

    $edition = Edition::where('slug', 'avenir-durable-2025')->firstOrFail();

    expect(Submission::where('edition_id', $edition->id)->where('ref_code', 'like', 'AVD-%')->count())->toBe(200);
});
