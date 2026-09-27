<?php

use App\Models\Edition;
use Illuminate\Support\Carbon;

function edStub(?string $start, ?string $end): Edition
{
    $e = new Edition;
    $e->date_start = $start ? Carbon::parse($start) : null;
    $e->date_end = $end ? Carbon::parse($end) : null;

    return $e;
}

it('formate une plage sur deux mois sans masquer le mois de début', function () {
    Carbon::setLocale('fr');
    expect(edStub('2026-09-27', '2026-10-04')->dateRangeLabel())
        ->toBe('27 septembre - 4 octobre 2026');
});

it('formate une plage dans le même mois', function () {
    Carbon::setLocale('fr');
    expect(edStub('2026-09-15', '2026-09-17')->dateRangeLabel())
        ->toBe('15 - 17 septembre 2026');
});

it('formate une plage sur deux années', function () {
    Carbon::setLocale('fr');
    expect(edStub('2026-12-30', '2027-01-03')->dateRangeLabel())
        ->toBe('30 décembre 2026 - 3 janvier 2027');
});

it('formate une date unique (pas de fin, ou fin = début)', function () {
    Carbon::setLocale('fr');
    expect(edStub('2026-09-27', null)->dateRangeLabel())->toBe('27 septembre 2026');
    expect(edStub('2026-09-27', '2026-09-27')->dateRangeLabel())->toBe('27 septembre 2026');
});

it('retourne null sans date de début', function () {
    expect(edStub(null, null)->dateRangeLabel())->toBeNull();
});

it('n’utilise jamais de tiret typographique', function () {
    Carbon::setLocale('fr');
    foreach ([['2026-09-27', '2026-10-04'], ['2026-09-15', '2026-09-17'], ['2026-12-30', '2027-01-03']] as [$s, $e]) {
        expect(edStub($s, $e)->dateRangeLabel())->not->toMatch('/[\x{2012}-\x{2015}\x{2212}]/u');
    }
});
