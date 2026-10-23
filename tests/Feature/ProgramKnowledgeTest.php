<?php

use App\AI\AiManager;
use App\AI\Contracts\AiProvider;
use App\Models\Claim;
use App\Models\EventSession;
use App\Models\Formation;
use App\Models\FormationEnrollment;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\Review;
use App\Models\Slot;
use App\Notifications\SystemNotification;
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
        ->and($digest)->toContain('08:30-11:30')
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

it('notifie l’auteur quand l’admin reprogramme son créneau (updateSlot)', function () {
    $slot = placedSlot();
    $user = $slot->submission->participant->user;
    expect($user->notifications()->count())->toBe(0);

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.program.slots.update', $slot), [
            'slDay' => '2026-10-06',
            'slStart' => '14:00',
            'slRoom' => 'Salle 5',
            'submission_id' => (string) $slot->submission_id, // la communication reste placée
        ])->assertSessionHasNoErrors();

    $notifs = $user->fresh()->notifications()->where('type', SystemNotification::class)->get();
    expect($notifs)->toHaveCount(1)
        ->and($notifs->first()->data['kind'])->toBe('schedule_change')
        ->and($notifs->first()->data['title'])->toBe('Votre créneau de passage a changé');
});

it('ne notifie PAS lors d’un placement initial via updateSlot', function () {
    $sub = seededSubmission('accepted');
    // Créneau libre (jamais horodaté pour cette communication) puis planifié.
    $slot = Slot::create([
        'event_id' => $sub->event->id, 'submission_id' => 0, 'participant_id' => 0,
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->put(route('admin.program.slots.update', $slot), [
            'slDay' => '2026-10-06', 'slStart' => '14:00', 'slRoom' => 'Salle 5',
            'submission_id' => (string) $sub->id,
        ])->assertSessionHasNoErrors();

    expect($sub->participant->user->fresh()->notifications()->count())->toBe(0);
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

it('donne à Gesco les engagements du participant (formation, rôle, réclamation)', function () {
    $captured = [];
    capturingAi($captured);

    $sub = seededSubmission('accepted');
    $event = $sub->event;
    $account = $sub->participant->user;

    // Formation suivie (réglée).
    $formation = Formation::create([
        'event_id' => $event->id, 'title' => 'Méthodologie de la recherche',
        'starts_at' => '2026-10-08 14:00', 'status' => 'published', 'fee' => 10000,
    ]);
    FormationEnrollment::create([
        'formation_id' => $formation->id, 'event_id' => $event->id, 'user_id' => $account->id,
        'participant_id' => $sub->participant_id, 'status' => 'paid',
    ]);

    // Rôle : rapporteur d'une séance (rapproché par e-mail).
    EventSession::create([
        'event_id' => $event->id, 'type' => 'atelier', 'label' => 'Atelier Santé',
        'rapporteur_email' => $account->email,
    ]);

    // Réclamation déposée.
    Claim::create([
        'event_id' => $event->id, 'user_id' => $account->id, 'type' => 'reclamation',
        'subject' => 'Problème de badge', 'message' => 'Mon badge est illisible.', 'status' => 'open',
    ]);

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Quels sont mes engagements ?'])
        ->assertOk();

    $prompt = implode("\n", $captured);
    expect($prompt)->toContain('Méthodologie de la recherche')
        ->and($prompt)->toContain('RAPPORTEZ')
        ->and($prompt)->toContain('Atelier Santé')
        ->and($prompt)->toContain('réclamations');
});

it('donne au copilote admin les relectures, formations et réclamations', function () {
    $captured = [];
    capturingAi($captured);

    $sub = seededSubmission('accepted');
    $event = $sub->event;

    // Une relecture confiée à un évaluateur actif (en attente).
    $reviewer = $event->participants()->create([
        'first_name' => 'Komi', 'last_name' => 'Relecteur',
        'email' => 'relecteur@example.tg', 'role' => 'reviewer',
    ]);
    Review::create([
        'submission_id' => $sub->id, 'reviewer_id' => $reviewer->id, 'status' => 'invited',
    ]);

    // Une réclamation ouverte.
    Claim::create([
        'event_id' => $event->id, 'type' => 'incident', 'subject' => 'Panne', 'message' => 'x', 'status' => 'open',
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Fais-moi un point', 'event_id' => $event->id])
        ->assertOk();

    $prompt = implode("\n", $captured);
    expect($prompt)->toContain('Évaluations :')
        ->and($prompt)->toContain('Réclamations/incidents en cours');
});

it('donne au copilote admin l’annuaire nominatif (noms, e-mails) - mais pas à Gesco', function () {
    $adminCaptured = [];
    capturingAi($adminCaptured);

    $sub = seededSubmission('accepted'); // participant Awa Kodjo <awa@example.tg>
    $event = $sub->event;

    // Copilote admin : l'annuaire nominatif est dans le prompt.
    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Quel est l’e-mail de Awa Kodjo ?', 'event_id' => $event->id])
        ->assertOk();
    $adminPrompt = implode("\n", $adminCaptured);
    expect($adminPrompt)->toContain('ANNUAIRE DES PARTICIPANTS')
        ->and($adminPrompt)->toContain('Awa Kodjo')
        ->and($adminPrompt)->toContain('awa@example.tg');

    // Gesco (public) : AUCUN annuaire nominatif de tiers dans son prompt.
    $gescoCaptured = [];
    capturingAi($gescoCaptured);
    $this->postJson(route('gesco.ask'), ['question' => 'Donne-moi la liste des participants.'])->assertOk();
    expect(implode("\n", $gescoCaptured))->not->toContain('ANNUAIRE DES PARTICIPANTS');
});

it('signale au copilote admin les jalons et tâches de projet en retard', function () {
    $captured = [];
    capturingAi($captured);

    $sub = seededSubmission('accepted');
    $event = $sub->event;

    ProjectMilestone::create([
        'event_id' => $event->id, 'title' => 'Clôture des inscriptions',
        'due_date' => now()->subDays(5)->toDateString(), 'status' => 'todo',
    ]);
    ProjectTask::create([
        'event_id' => $event->id, 'title' => 'Imprimer les badges',
        'due_date' => now()->subDays(2)->toDateString(), 'status' => 'todo', 'priority' => 'high',
    ]);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.ask'), ['question' => 'Quoi en retard ?', 'event_id' => $event->id])
        ->assertOk();

    $prompt = implode("\n", $captured);
    expect($prompt)->toContain('Projet - EN RETARD')
        ->and($prompt)->toContain('Clôture des inscriptions')
        ->and($prompt)->toContain('Imprimer les badges');
});

it('donne à Gesco le montant exact dû et l’échéance du participant', function () {
    $captured = [];
    capturingAi($captured);

    $sub = seededSubmission('accepted');
    $event = $sub->event;
    $event->update([
        'date_start' => '2026-10-05',
        'settings' => array_merge($event->settings ?? [], [
            'fees' => [['label' => 'Enseignants-chercheurs', 'amount' => 35000, 'grades' => [], 'default' => true]],
        ]),
    ]);
    $account = $sub->participant->user;

    $this->actingAs($account, 'account')
        ->postJson(route('gesco.ask'), ['question' => 'Combien je dois payer ?'])
        ->assertOk();

    $prompt = implode("\n", $captured);
    expect($prompt)->toContain('Montant dû')
        ->and($prompt)->toContain('35 000 FCFA')
        ->and($prompt)->toContain('Enseignants-chercheurs')
        ->and($prompt)->toContain('05/10/2026');
});

/**
 * ⚠️ Ce compte part à l'assistante **PUBLIQUE** : il ne doit porter que les
 * communications que l'événement présente. Une communication planifiée **puis
 * refusée** garde son créneau, et Gesco annonçait « 2 communications » là où la
 * séance n'en tient qu'une (cf. `Submission::scopeAcceptedCanonical`).
 */
it('ne compte au programme public que les communications retenues', function () {
    $slot = placedSlot();
    $event = $slot->event;

    // Même séance, un créneau de plus : une communication refusée après coup.
    $refusee = $event->submissions()->create([
        'participant_id' => $slot->participant_id,
        'ref_code' => '26-002',
        'title' => 'Communication refusée après planification',
        'abstract' => str_repeat('Contexte. ', 10),
        'track' => 'Sciences', 'type' => 'oral', 'status' => 'rejected', 'revision_of' => 0,
        'authors' => [['name' => 'Kossi Mensah', 'email' => 'kossi@example.tg', 'corresponding' => true]],
    ]);
    Slot::create([
        'event_id' => $event->id, 'session_id' => $slot->session_id,
        'submission_id' => $refusee->id, 'participant_id' => $slot->participant_id,
        'session_label' => 'Atelier Innovation', 'room' => 'Salle 2',
        'starts_at' => '2026-10-05 09:30', 'ends_at' => '2026-10-05 10:00',
    ]);

    $digest = ProgramKnowledge::overview($event);

    expect($digest)->toContain('1 communication')
        ->and($digest)->not->toContain('2 communications');
});
