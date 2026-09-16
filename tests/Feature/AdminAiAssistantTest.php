<?php

use App\AI\Contracts\AiProvider;
use App\Models\User;

// fakeAiProvider(), bindFakeAi() -> GenerateReportTest ; adminStaff(), openEdition() -> helpers

it('le copilote admin répond au staff', function () {
    bindFakeAi(fakeAiProvider(['answer' => 'Voici une synthèse de l’édition active.']));

    $this->actingAs(adminStaff(), 'web')
        ->get(route('admin.ai'))
        ->assertOk()
        ->assertSee('copilote', false);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Résume l’édition active.'])
        ->assertOk()
        ->assertJsonPath('answer', 'Voici une synthèse de l’édition active.');
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

        public function key(): string { return 'fake'; }

        public function label(): string { return 'Fake'; }

        public function isAvailable(): bool { return true; }

        public function supportsPdf(): bool { return true; }

        public function extract(string $instruction, array $files = []): array
        {
            $this->c->prompt = $instruction;

            return ['answer' => 'ok', 'proposal' => null];
        }
    });

    $edition = openEdition();
    $staff = User::create(['first_name' => 'Carine', 'last_name' => 'Comptable', 'email' => 'compta@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($edition->id, ['role' => 'accounting']);

    $this->actingAs($staff, 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Où en sont les inscriptions ?', 'edition_id' => $edition->id])
        ->assertOk();

    // Le prompt décrit la fonction (Comptabilité) et le périmètre (Paiements).
    expect($captured->prompt)->toContain('Comptabilité');
    expect($captured->prompt)->toContain('Paiements');
    expect($captured->prompt)->toContain('PÉRIMÈTRE');
});
