<?php

use App\Models\Event;
use App\Models\Participant;
use App\Models\Submission;
use App\Models\User;
use App\Support\EventStorage;
use Database\Seeders\ColloqueEauTogoComplementSeeder;
use Database\Seeders\ColloqueEauTogoSeeder;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * **Le complément AJOUTE les résumés arrivés après le premier import.**
 *
 * ⚠️ Il tourne sur une base qui porte déjà la première vague : c'est la
 * situation de la production. Le seeder de base est donc exécuté d'abord, et
 * tout ce qui suit mesure le DELTA.
 */
beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolesAndAdminSeeder::class);
    $this->seed(ColloqueEauTogoSeeder::class);
    $this->event = Event::where('slug', 'premier-colloque-eau-togo-2026')->firstOrFail();
});

it('ajoute les 13 résumés sans toucher aux 49 premiers', function () {
    $avant = Submission::where('event_id', $this->event->id)->pluck('ref_code')->sort()->values()->all();
    expect($avant)->toHaveCount(49);

    $this->seed(ColloqueEauTogoComplementSeeder::class);

    $apres = Submission::where('event_id', $this->event->id)->pluck('ref_code')->sort()->values()->all();
    expect($apres)->toHaveCount(62)
        // Les 49 d'origine sont intacts : aucune référence n'a bougé.
        ->and(array_intersect($avant, $apres))->toHaveCount(49)
        ->and(array_values(array_diff($apres, $avant)))->toBe([
            'EAU26-050', 'EAU26-051', 'EAU26-052', 'EAU26-053', 'EAU26-054',
            'EAU26-055', 'EAU26-056', 'EAU26-057', 'EAU26-058', 'EAU26-059',
            'EAU26-060', 'EAU26-061', 'EAU26-062',
        ]);
});

it('⚠️ ne crée PAS un second compte quand l’auteur a changé d’adresse', function () {
    // La ligne 31 revient avec une adresse neuve et le MÊME téléphone.
    $ancien = User::whereRaw('LOWER(email) = ?', ['kpatchasamuel043@gmail.com'])->firstOrFail();
    $comptesAvant = User::count();
    $fichesAvant = Participant::where('event_id', $this->event->id)->count();

    $this->seed(ColloqueEauTogoComplementSeeder::class);

    expect(User::whereRaw('LOWER(email) = ?', ['samuelkpatcha91@gmail.com'])->exists())->toBeFalse()
        ->and(User::count())->toBe($comptesAvant + 10)
        ->and(Participant::where('event_id', $this->event->id)->count())->toBe($fichesAvant + 10);

    // Et son nouveau résumé est bien rattaché à la personne déjà connue.
    $nouveau = Submission::where('event_id', $this->event->id)
        ->get()->first(fn (Submission $s) => ($s->meta['source_row'] ?? null) === 31
            && ($s->meta['import_batch'] ?? null) === 'complement-2026-10-26');

    expect($nouveau)->not->toBeNull()
        ->and($nouveau->participant->user_id)->toBe($ancien->id);
});

it('⚠️ se rejoue sans rien dupliquer', function () {
    $this->seed(ColloqueEauTogoComplementSeeder::class);
    $comptes = User::count();
    $fiches = Participant::where('event_id', $this->event->id)->count();

    $this->seed(ColloqueEauTogoComplementSeeder::class);

    expect(Submission::where('event_id', $this->event->id)->count())->toBe(62)
        ->and(User::count())->toBe($comptes)
        ->and(Participant::where('event_id', $this->event->id)->count())->toBe($fiches);
});

it('⚠️ reprend la numérotation APRÈS une référence saisie à la main', function () {
    // Le back-office a déjà créé EAU26-050 : le complément ne doit pas l'écraser.
    $fiche = Participant::where('event_id', $this->event->id)->firstOrFail();
    Submission::create([
        'event_id' => $this->event->id,
        'participant_id' => $fiche->id,
        'ref_code' => 'EAU26-050',
        'title' => 'Communication saisie au back-office',
        'abstract' => str_repeat('Contexte. ', 10),
        'type' => 'oral',
        'status' => 'submitted',
        'revision_of' => 0,
    ]);

    $this->seed(ColloqueEauTogoComplementSeeder::class);

    expect(Submission::where('event_id', $this->event->id)->where('ref_code', 'EAU26-050')->value('title'))
        ->toBe('Communication saisie au back-office')
        ->and(Submission::where('event_id', $this->event->id)->where('ref_code', 'EAU26-063')->exists())
        ->toBeTrue();
});

it('dépose chaque pièce Word sous sa référence, intacte', function () {
    $this->seed(ColloqueEauTogoComplementSeeder::class);

    $neuves = Submission::where('event_id', $this->event->id)->get()
        ->filter(fn (Submission $s) => ($s->meta['import_batch'] ?? null) === 'complement-2026-10-26');

    expect($neuves)->toHaveCount(13);

    $disk = Storage::disk('public');
    foreach ($neuves as $submission) {
        $chemin = EventStorage::submissions($this->event)."/{$submission->ref_code}.docx";
        expect($disk->exists($chemin))->toBeTrue("Pièce absente : {$submission->ref_code}")
            ->and(hash('sha256', (string) $disk->get($chemin)))->toBe($submission->meta['file_sha256']);
    }
});

it('⚠️ refuse de tourner sans le colloque, au lieu de le recréer', function () {
    Submission::query()->delete();
    Participant::query()->delete();
    Event::query()->delete();

    expect(fn () => $this->seed(ColloqueEauTogoComplementSeeder::class))
        ->toThrow(RuntimeException::class, 'ColloqueEauTogoSeeder');
});

it('rattache chaque nouveau résumé à son auteur correspondant', function () {
    $this->seed(ColloqueEauTogoComplementSeeder::class);

    $neuves = Submission::with('authorLinks')->where('event_id', $this->event->id)->get()
        ->filter(fn (Submission $s) => ($s->meta['import_batch'] ?? null) === 'complement-2026-10-26');

    foreach ($neuves as $submission) {
        $lien = $submission->authorLinks->firstWhere('author_index', 0);
        expect($lien)->not->toBeNull("Lien d'auteur absent : {$submission->ref_code}")
            ->and($lien->role)->toBe('corresponding')
            ->and($lien->status)->toBe('linked')
            ->and($lien->user_id)->toBe($submission->participant->user_id);
    }
});

it('⚠️ ne double pas une fiche ouverte au guichet, sans compte', function () {
    // Le secrétariat inscrit quelqu'un sur place (fiche sans compte), et cette
    // personne dépose ensuite un résumé depuis le formulaire, avec une AUTRE
    // adresse. Seul le téléphone les rapproche.
    $fiche = Participant::create([
        'event_id' => $this->event->id,
        'first_name' => 'Nestor Dissane',
        'last_name' => 'BAGUILIMA',
        'email' => 'inscrit-au-guichet@example.org',
        'phone' => '+228 70 47 07 57',
        'role' => 'attendee',
    ]);
    $avant = Participant::where('event_id', $this->event->id)->count();

    $this->seed(ColloqueEauTogoComplementSeeder::class);

    expect(Participant::where('event_id', $this->event->id)->count())->toBe($avant + 9)
        ->and($fiche->fresh()->user_id)->not->toBeNull();

    $resume = Submission::where('event_id', $this->event->id)->get()
        ->first(fn (Submission $s) => ($s->meta['source_row'] ?? null) === 48);

    expect($resume)->not->toBeNull()
        ->and($resume->participant_id)->toBe($fiche->id);
});
