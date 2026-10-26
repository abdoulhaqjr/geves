<?php

use App\Models\Event;
use App\Models\Submission;
use Database\Seeders\ColloqueEauTogoCoauthorsSeeder;
use Database\Seeders\ColloqueEauTogoComplementSeeder;
use Database\Seeders\ColloqueEauTogoSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * **Les co-auteurs libres complètent les communications, sans rien casser.**
 *
 * ⚠️ On tourne sur la base réelle de la production : import de base + complément,
 * PUIS le seeder de co-auteurs. Il ne doit toucher que `submission.authors`.
 */
beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(ColloqueEauTogoSeeder::class);
    $this->seed(ColloqueEauTogoComplementSeeder::class);
    $this->seed(ColloqueEauTogoCoauthorsSeeder::class);
    $this->event = Event::where('slug', 'premier-colloque-eau-togo-2026')->firstOrFail();
});

function eauTogoSub(int $eventId, string $needle): Submission
{
    return Submission::where('event_id', $eventId)->get()
        ->first(fn (Submission $s) => str_contains((string) ($s->meta['coauthors_raw'] ?? ''), $needle))
        ?? throw new RuntimeException("Soumission introuvable pour « {$needle} ».");
}

it('ajoute les co-auteurs relus à une communication, l’auteur correspondant intact', function () {
    $sub = eauTogoSub($this->event->id, 'Bida Yandaï Olivera');
    $authors = $sub->authors;

    // authors[0] reste l'auteur correspondant, inchangé.
    expect($authors[0]['corresponding'])->toBeTrue()
        ->and($authors[0]['name'])->toBe('Eric Bienvenu Armand FOTO');

    $coauteurs = collect($authors)->slice(1)->pluck('name')->values()->all();
    expect($coauteurs)->toBe([
        'Bida Yandaï Olivera', 'Nicole Poumayé', 'Olga Biteman', 'Oscar Allahdin', 'Eric Foto',
    ]);
    // Un co-auteur n'est jamais marqué correspondant.
    expect(collect($authors)->slice(1)->every(fn ($a) => $a['corresponding'] === false))->toBeTrue();
});

it('ne redit pas le déposant quand il se re-liste en co-auteur', function () {
    // Ligne 30 : la cellule rouvre par « Abba Amba BRAHIM », qui EST le déposant.
    $sub = eauTogoSub($this->event->id, 'Abba Amba BRAHIM');
    $noms = collect($sub->authors)->pluck('name');

    // Le déposant n'apparaît qu'UNE fois (dédoublonnage par ensemble de mots).
    $brahim = $noms->filter(fn (string $n) => str_contains(mb_strtolower($n), 'brahim'));
    expect($brahim)->toHaveCount(1)
        ->and($sub->authors[0]['corresponding'])->toBeTrue();
    // Les cinq autres co-auteurs sont bien là.
    expect($noms->slice(1)->values()->all())->toBe([
        'Simon AHOUANSOU MONTCHO', 'Pierre K. HOUNDONOUGBO', 'Romaric K. LALEYE',
        'Antoine CHIKOU', 'Philippe A. LALEYE',
    ]);
});

it('laisse une communication « Néant » avec son seul auteur correspondant', function () {
    $sub = Submission::where('event_id', $this->event->id)->get()
        ->first(fn (Submission $s) => trim((string) ($s->meta['coauthors_raw'] ?? '')) === 'Néant');
    expect($sub)->not->toBeNull()
        ->and($sub->authors)->toHaveCount(1)
        ->and($sub->authors[0]['corresponding'])->toBeTrue();
});

it('complète 39 communications : 40 cellules relues, moins celle qui ne liste que le déposant', function () {
    // 40 cellules portent des co-auteurs relus, mais L6 (KUEVIAKOE) ne « liste »
    // que le déposant lui-même : après dédoublonnage il ne reste personne, et
    // cette communication garde son seul auteur correspondant. D'où 39, pas 40.
    $avecCoauteurs = Submission::where('event_id', $this->event->id)->get()
        ->filter(fn (Submission $s) => count($s->authors ?? []) > 1)
        ->count();
    expect($avecCoauteurs)->toBe(39);

    $kueviakoe = eauTogoSub($this->event->id, 'KUEVIAKOE Kanle Grace-Natacha');
    expect($kueviakoe->authors)->toHaveCount(1)
        ->and($kueviakoe->authors[0]['corresponding'])->toBeTrue();
});

it('est idempotent : un second passage ne change rien', function () {
    $avant = Submission::where('event_id', $this->event->id)
        ->orderBy('ref_code')->pluck('authors', 'ref_code')->toArray();

    $this->seed(ColloqueEauTogoCoauthorsSeeder::class);

    $apres = Submission::where('event_id', $this->event->id)
        ->orderBy('ref_code')->pluck('authors', 'ref_code')->toArray();
    expect($apres)->toBe($avant);
});
