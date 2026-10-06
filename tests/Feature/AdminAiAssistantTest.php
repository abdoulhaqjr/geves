<?php

use App\AI\Contracts\AiProvider;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest ; adminStaff(), openEdition() -> helpers

it('le copilote admin répond au staff', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Voici une synthèse de l’événement actif.']));

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.ai'))
        ->assertOk()
        ->assertSee('copilote', false);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Résume l’événement actif.'])
        ->assertOk()
        ->assertJsonPath('answer', 'Voici une synthèse de l’événement actif.')
        // Plus de mécanisme « proposition » à valider dans le vide : seul le chemin
        // « action exécutable » subsiste (clé "action").
        ->assertJsonMissingPath('proposal');
});

it('ne propose plus de « proposition » descriptive (mécanisme retiré)', function () {
    // Même si l'IA renvoie encore une "proposal", le serveur ne l'expose plus.
    bindFakeAi(fakeAiProvider([
        'answer' => 'Voici une piste.',
        'proposal' => ['title' => 'Export manuel', 'detail' => 'À faire', 'impact' => 'Aucun'],
    ]));

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Que puis-je faire ?'])
        ->assertOk()
        ->assertJsonMissingPath('proposal');

    // La route de décision (approuver/modifier/refuser) n'existe plus.
    expect(Route::has('admin.ai.decide'))->toBeFalse();
});

it('exige une saisie', function () {
    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => ''])
        ->assertStatus(422);
});

it('cadre le copilote sur la fonction dans le colloque sélectionné', function () {
    // Fournisseur IA qui capture le prompt transmis.
    $captured = new stdClass;
    $captured->prompt = '';
    bindFakeAi(new class($captured) implements AiProvider
    {
        public function __construct(private object $c) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            $this->c->prompt = $instruction;

            return ['answer' => 'ok', 'proposal' => null];
        }
    });

    $event = openEdition();
    $staff = User::create(['first_name' => 'Carine', 'last_name' => 'Comptable', 'email' => 'compta@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($event->id, ['role' => 'accounting']);

    $this->actingAs($staff, 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Où en sont les inscriptions ?', 'event_id' => $event->id])
        ->assertOk();

    // Le prompt décrit la fonction (Comptabilité) et le périmètre (Paiements).
    expect($captured->prompt)->toContain('Comptabilité');
    expect($captured->prompt)->toContain('Paiements');
    expect($captured->prompt)->toContain('PÉRIMÈTRE');
});

it('cadre le copilote sur la cohérence des nombres (pas de sous-découpage inventé)', function () {
    // Capture le prompt transmis au fournisseur.
    $captured = new stdClass;
    $captured->prompt = '';
    bindFakeAi(new class($captured) implements AiProvider
    {
        public function __construct(private object $c) {}

        public function key(): string
        {
            return 'fake';
        }

        public function label(): string
        {
            return 'Fake';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return true;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            $this->c->prompt = $instruction;

            return ['answer' => 'ok'];
        }
    });

    $event = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Relance les paiements', 'event_id' => $event->id])
        ->assertOk();

    // Le garde-fou « cohérence des nombres » est bien injecté : le chiffre qui fait
    // foi est le compte réel de l'action, et Marc ne doit pas fabriquer de sous-découpage.
    expect($captured->prompt)->toContain('COHÉRENCE DES NOMBRES');
    expect($captured->prompt)->toContain('CHIFFRE QUI FAIT');
});
