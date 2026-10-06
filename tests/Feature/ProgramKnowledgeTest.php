<?php

use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Models\EventSession;
use App\Models\Slot;
use App\Support\ProgramKnowledge;

// Fournisseur IA qui CAPTURE l'instruction (le prompt) pour vérifier le contexte
// réellement transmis à l'assistante. Le dernier prompt est lu via $captured.
function capturingAi(array &$captured, array $result = ['answer' => 'Réponse.', 'claim' => null]): void
{
    $provider = new class($result, $captured) implements AiProvider
    {
        public function __construct(private array $result, private array &$captured) {}

        public function key(): string
        {
            return 'capture';
        }

        public function label(): string
        {
            return 'Capture';
        }

        public function isAvailable(): bool
        {
            return true;
        }

        public function supportsPdf(): bool
        {
            return false;
        }

        public function extract(string $instruction, array $files = [], ?string $tier = null): array
        {
            $this->captured[] = $instruction;

            return $this->result;
        }
    };

    $manager = Mockery::mock(AiManager::class);
    $manager->shouldReceive('resolve')->andReturn($provider);
    $manager->shouldReceive('extract')->andReturnUsing(
        fn (string $instruction, array $files = [], ?string $tier = null, ?string $preferKey = null) => $provider->extract($instruction, $files, $tier)
    );
    app()->instance(AiManager::class, $manager);
}

/** Crée une communication acceptée placée dans un créneau horodaté. */
function placedSlot(array $slot = []): Slot
{
    $sub = seededSubmission('accepted');
    $event = $sub->event;

    $session = EventSession::create([
        'event_id' => $event->id, 'type' => 'atelier', 'label' => 'Atelier Innovation',
        'room' => 'Salle 2', 'session_day' => '2026-10-05',
        'starts_at' => '2026-10-05 08:30', 'ends_at' => '2026-10-05 11:30',
        'moderator_name' => 'Dr Kossi', 'rapporteur_name' => 'Mme Adjo',
    ]);

    return Slot::create(array_merge([
        'event_id' => $event->id,
        'submission_id' => $sub->id,
        'participant_id' => $sub->participant_id,
        'session_id' => $session->id,
        'session_label' => 'Atelier Innovation',
        'room' => 'Salle 2',
        'starts_at' => '2026-10-05 09:00',
        'ends_at' => '2026-10-05 09:30',
    ], $slot));
}

it('consigne une reprogrammation quand l’horaire d’un créneau placé change', function () {
    $slot = placedSlot();
    expect($slot->wasRescheduled())->toBeFalse();

    $slot->update(['starts_at' => '2026-10-06 14:00', 'ends_at' => '2026-10-06 14:30', 'room' => 'Salle 5']);

    $slot->refresh();
    expect($slot->wasRescheduled())->toBeTrue();

    $change = $slot->lastReschedule();
    expect($change['from']['room'])->toBe('Salle 2')
        ->and($change['to']['room'])->toBe('Salle 5')
        ->and($change['from']['starts_at'])->toContain('2026-10-05T09:00')
        ->and($change['to']['starts_at'])->toContain('2026-10-06T14:00')
        ->and($change['at'])->not->toBeNull();
});

it('ne consigne RIEN lors d’un placement initial (aucun horaire précédent)', function () {
    $sub = seededSubmission('accepted');
    // Créneau créé SANS horaire, puis planifié : c'est une première planification.
    $slot = Slot::create([
        'event_id' => $sub->event->id, 'submission_id' => $sub->id,
        'participant_id' => $sub->participant_id,
    ]);

    $slot->update(['starts_at' => '2026-10-05 09:00', 'ends_at' => '2026-10-05 09:30', 'room' => 'Salle 1']);

    expect($slot->fresh()->wasRescheduled())->toBeFalse();
});

it('ne consigne pas un changement NON lié à l’horaire/salle/séance', function () {
    $slot = placedSlot();
    $slot->update(['presented_at' => now()]);

    expect($slot->fresh()->wasRescheduled())->toBeFalse();
});

it('décrit le programme (séances) en vue d’ensemble', function () {
    $slot = placedSlot();
    $digest = ProgramKnowledge::overview($slot->event);

    expect($digest)->toContain('PROGRAMME')
        ->and($digest)->toContain('Atelier Innovation')
        ->and($digest)->toContain('Salle 2')
        ->and($digest)->toContain('08:30–11:30')
        ->and($digest)->toContain('modérateur Dr Kossi');
});

it('décrit le planning détaillé des créneaux de communication', function () {
    $slot = placedSlot();
    $digest = ProgramKnowledge::planning($slot->event);

    expect($digest)->toContain('PLANNING DÉTAILLÉ')
        ->and($digest)->toContain('Innovation durable')  // titre de la communication
        ->and($digest)->toContain('26-001')              // réf
        ->and($digest)->toContain('Salle 2');
});

it('donne à l’assistante le programme personnel du participant connecté', function () {
    $captured = [];
    capturingAi($captured);

    $slot = placedSlot();
    $account = $slot->submission->participant->user;

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Quand est-ce que je communique ?'])
        ->assertOk();

    $prompt = implode("\n", $captured);
    expect($prompt)->toContain('Programme personnel')
        ->and($prompt)->toContain('Innovation durable')
        ->and($prompt)->toContain('Salle 2');
});

it('informe le participant d’une reprogrammation (avant / après)', function () {
    $captured = [];
    capturingAi($captured);

    $slot = placedSlot();
    $slot->update(['starts_at' => '2026-10-06 14:00', 'ends_at' => '2026-10-06 14:30', 'room' => 'Salle 5']);
    $account = $slot->submission->participant->user;

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Mon créneau a-t-il changé ?'])
        ->assertOk();

    $prompt = implode("\n", $captured);
    expect($prompt)->toContain('reprogrammé')
        ->and($prompt)->toContain('Salle 5');   // la nouvelle salle
});
