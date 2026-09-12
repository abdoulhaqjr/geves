<?php

use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Notification;

it('affiche le hub Projet d’une édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.project.index', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('piloté comme un projet')
        ->assertSee('Initialiser le projet'); // projet vierge
});

it('réserve la page Projet aux rôles porteurs de la capacité « project »', function () {
    $edition = openEdition();
    $withCap = User::factory()->create(['access_level' => User::ACCESS_USER]);   // secrétariat → a « project »
    $withoutCap = User::factory()->create(['access_level' => User::ACCESS_USER]); // accueil → n'a pas « project »
    $edition->team()->attach($withCap->id, ['role' => 'secretariat']);
    $edition->team()->attach($withoutCap->id, ['role' => 'reception']);

    $this->actingAs($withCap, 'web')
        ->get(route('admin.project.index', ['edition_id' => $edition->id]))->assertOk();
    $this->actingAs($withoutCap, 'web')
        ->get(route('admin.project.index', ['edition_id' => $edition->id]))->assertForbidden();
});

it('initialise le projet avec le cycle de vie type (jalons + tâches), idempotent', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $edition->update(['submission_deadline' => now()->addMonth(), 'date_start' => now()->addMonths(3)]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.project.seed'), ['edition_id' => $edition->id])
        ->assertSessionHasNoErrors();

    expect(ProjectTask::where('edition_id', $edition->id)->count())->toBeGreaterThan(0);
    expect(ProjectMilestone::where('edition_id', $edition->id)->count())->toBeGreaterThan(0);
    // Le jalon « Clôture des soumissions » hérite de la date limite de l'édition.
    $ms = ProjectMilestone::where('edition_id', $edition->id)->where('title', 'Clôture des soumissions')->first();
    expect($ms?->due_date?->toDateString())->toBe($edition->submission_deadline->toDateString());

    // Second amorçage ignoré (projet déjà peuplé).
    $before = ProjectTask::where('edition_id', $edition->id)->count();
    $this->actingAs($staff, 'web')->post(route('admin.project.seed'), ['edition_id' => $edition->id]);
    expect(ProjectTask::where('edition_id', $edition->id)->count())->toBe($before);

    // La vue peuplée (kanban + liste + jalons + calendrier) se rend sans erreur.
    $this->actingAs($staff, 'web')
        ->get(route('admin.project.index', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Avancement global')
        ->assertSee('Tableau')
        ->assertSee('Gantt')
        ->assertSee('Mes tâches')
        ->assertSee('Calendrier')
        ->assertSee('projectCalendar', false); // le conteneur du calendrier des échéances
});

it('associe un lien contextuel à une tâche (modèle + saisie)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    // Les tâches modèles portent un lien vers la feature concernée.
    $this->actingAs($staff, 'web')->post(route('admin.project.seed'), ['edition_id' => $edition->id]);
    expect(ProjectTask::where('edition_id', $edition->id)->where('title', 'like', 'Composer le programme%')->value('link_key'))->toBe('program');

    // Saisie manuelle : lien accepté s'il est connu, ignoré (erreur validation) sinon.
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'Ouvrir paiements', 'status' => 'todo', 'priority' => 'normal',
        'link_key' => 'payments',
    ])->assertSessionHasNoErrors();
    expect(ProjectTask::where('title', 'Ouvrir paiements')->value('link_key'))->toBe('payments');

    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'Lien bidon', 'status' => 'todo', 'priority' => 'normal',
        'link_key' => 'inexistant',
    ])->assertSessionHasErrors('link_key');
});

it('CRUD tâche en AJAX renvoie la carte JSON (sans rechargement)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    // Création → carte JSON.
    $create = $this->actingAs($staff, 'web')->postJson(
        route('admin.project.tasks.store', ['edition_id' => $edition->id]),
        ['title' => 'Tâche AJAX', 'status' => 'todo', 'priority' => 'high', 'link_key' => 'payments', 'start_date' => '2026-10-01', 'due_date' => '2026-10-20', 'checklist_json' => json_encode([['text' => 'a', 'done' => false]])]
    )->assertOk();
    $create->assertJsonPath('task.title', 'Tâche AJAX');
    $create->assertJsonPath('task.priorityLabel', 'Haute');
    $create->assertJsonPath('task.checkTotal', 1);
    $create->assertJsonPath('task.start_date', '2026-10-01');
    expect($create->json('task.linkUrl'))->toContain('edition_id='.$edition->id);
    $id = $create->json('task.id');

    // Mise à jour → carte JSON à jour.
    $this->actingAs($staff, 'web')->putJson(route('admin.project.tasks.update', $id), [
        'title' => 'Tâche AJAX modifiée', 'status' => 'done', 'priority' => 'normal',
    ])->assertOk()->assertJsonPath('task.title', 'Tâche AJAX modifiée')->assertJsonPath('task.status', 'done');

    // Suppression → ok JSON.
    $this->actingAs($staff, 'web')->deleteJson(route('admin.project.tasks.destroy', $id))
        ->assertOk()->assertJson(['ok' => true]);
    expect(ProjectTask::find($id))->toBeNull();
});

it('amorçage en AJAX renvoie l’état complet (tâches + jalons)', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $res = $this->actingAs($staff, 'web')
        ->postJson(route('admin.project.seed', ['edition_id' => $edition->id]))->assertOk();
    expect($res->json('tasks'))->not->toBeEmpty();
    expect($res->json('milestones'))->not->toBeEmpty();

    // Second amorçage en AJAX refusé (projet déjà peuplé) → 422.
    $this->actingAs($staff, 'web')
        ->postJson(route('admin.project.seed', ['edition_id' => $edition->id]))->assertStatus(422);
});

it('notifie le responsable quand une tâche lui est assignée (pas soi-même)', function () {
    Notification::fake();
    $staff = adminStaff();
    $edition = openEdition();
    $member = User::factory()->create();
    $edition->team()->attach($member->id, ['role' => 'secretariat']);

    // Assignation à un autre → notifié.
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store', ['edition_id' => $edition->id]), [
        'title' => 'À faire par Mia', 'status' => 'todo', 'priority' => 'normal', 'assignee_id' => $member->id,
    ]);
    Notification::assertSentTo($member, SystemNotification::class,
        fn ($n) => $n->kind === 'task_assigned');

    // Auto-assignation (le membre s'assigne à lui-même) → pas de notification.
    $this->actingAs($member, 'web')->post(route('admin.project.tasks.store', ['edition_id' => $edition->id]), [
        'title' => 'Pour moi', 'status' => 'todo', 'priority' => 'normal', 'assignee_id' => $member->id,
    ]);
    Notification::assertSentToTimes($member, SystemNotification::class, 1); // toujours 1 (pas de 2e pour l'auto-assignation)
});

it('exporte le projet en Excel et rend le rapport imprimable', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $this->actingAs($staff, 'web')->post(route('admin.project.seed', ['edition_id' => $edition->id]));

    $this->actingAs($staff, 'web')
        ->get(route('admin.project.export', ['edition_id' => $edition->id]))
        ->assertOk()->assertDownload();

    $this->actingAs($staff, 'web')
        ->get(route('admin.project.report', ['edition_id' => $edition->id]))
        ->assertOk()->assertSee('Rapport de projet')->assertSee('Avancement');
});

it('crée une tâche et l’ajoute en bas de sa colonne', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id,
        'title' => 'Réserver l’amphi',
        'status' => 'todo',
        'priority' => 'high',
        'phase' => 'Logistique',
    ])->assertSessionHasNoErrors();

    $task = ProjectTask::where('edition_id', $edition->id)->first();
    expect($task)->not->toBeNull();
    expect($task->title)->toBe('Réserver l’amphi');
    expect($task->priority)->toBe('high');
});

it('assigne une tâche à un membre de l’équipe ou à un admin, sinon ignore', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $member = User::factory()->create();                                             // équipe
    $admin = User::factory()->create(['access_level' => User::ACCESS_COLLOQUE_ADMIN]); // admin hors équipe
    $outsider = User::factory()->create();                                           // simple utilisateur hors équipe
    $edition->team()->attach($member->id, ['role' => 'secretariat']);

    // Membre de l'équipe → accepté.
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'T1', 'status' => 'todo', 'priority' => 'normal', 'assignee_id' => $member->id,
    ]);
    expect(ProjectTask::where('title', 'T1')->value('assignee_id'))->toBe($member->id);

    // Administrateur de colloque (hors équipe mais accès au projet) → accepté.
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'T2', 'status' => 'todo', 'priority' => 'normal', 'assignee_id' => $admin->id,
    ]);
    expect(ProjectTask::where('title', 'T2')->value('assignee_id'))->toBe($admin->id);

    // Simple utilisateur hors équipe → ignoré (null).
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'T3', 'status' => 'todo', 'priority' => 'normal', 'assignee_id' => $outsider->id,
    ]);
    expect(ProjectTask::where('title', 'T3')->value('assignee_id'))->toBeNull();
});

it('enregistre et assainit la checklist d’une tâche', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'Avec checklist', 'status' => 'todo', 'priority' => 'normal',
        'checklist_json' => json_encode([
            ['text' => 'Étape 1', 'done' => true],
            ['text' => '  ', 'done' => false],   // vide → ignorée
            ['text' => 'Étape 2', 'done' => false],
        ]),
    ])->assertSessionHasNoErrors();

    $task = ProjectTask::where('title', 'Avec checklist')->first();
    expect($task->checklist)->toHaveCount(2);
    expect($task->checklist[0])->toBe(['text' => 'Étape 1', 'done' => true]);
    expect($task->checklistProgress())->toBe([1, 2]);
});

it('ajoute et liste les commentaires d’une tâche (avec auteur)', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $task = ProjectTask::create(['edition_id' => $edition->id, 'title' => 'T', 'status' => 'todo', 'priority' => 'normal']);

    $this->actingAs($staff, 'web')->postJson(route('admin.project.tasks.comment', $task), ['body' => 'Premier commentaire'])
        ->assertOk()->assertJsonPath('comment.body', 'Premier commentaire');
    $this->actingAs($staff, 'web')->postJson(route('admin.project.tasks.comment', $task), ['body' => 'Deuxième']);

    $res = $this->actingAs($staff, 'web')->getJson(route('admin.project.tasks.comments', $task))->assertOk();
    $res->assertJsonCount(2, 'comments');
    // Ordre anté-chronologique : le plus récent d'abord.
    expect($res->json('comments.0.body'))->toBe('Deuxième');
    expect($res->json('comments.1.author'))->not->toBeNull();
});

it('déplace une tâche en kanban : nouveau statut + date d’achèvement + ordre', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $a = ProjectTask::create(['edition_id' => $edition->id, 'title' => 'A', 'status' => 'todo', 'priority' => 'normal', 'position' => 0]);
    $b = ProjectTask::create(['edition_id' => $edition->id, 'title' => 'B', 'status' => 'done', 'priority' => 'normal', 'position' => 0]);

    $this->actingAs($staff, 'web')->postJson(route('admin.project.tasks.move', $a), [
        'status' => 'done',
        'order' => [$b->id, $a->id],
    ])->assertOk()->assertJson(['ok' => true]);

    $a->refresh();
    expect($a->status)->toBe('done');
    expect($a->completed_at)->not->toBeNull();
    expect($a->position)->toBe(1);
    expect($b->fresh()->position)->toBe(0);
});

it('re-planifie une tâche et un jalon par glisser-déposer (calendrier)', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $task = ProjectTask::create(['edition_id' => $edition->id, 'title' => 'T', 'status' => 'todo', 'priority' => 'normal', 'due_date' => now()->toDateString()]);
    $ms = ProjectMilestone::create(['edition_id' => $edition->id, 'title' => 'J', 'due_date' => now()->toDateString()]);

    $this->actingAs($staff, 'web')->postJson(route('admin.project.tasks.reschedule', $task), ['due_date' => '2026-12-01'])
        ->assertOk()->assertJsonPath('task.id', $task->id);
    expect($task->fresh()->due_date->toDateString())->toBe('2026-12-01');

    $this->actingAs($staff, 'web')->postJson(route('admin.project.milestones.reschedule', $ms), ['due_date' => '2026-11-15'])
        ->assertOk()->assertJsonPath('milestone.id', $ms->id);
    expect($ms->fresh()->due_date->toDateString())->toBe('2026-11-15');
});

it('ajoute un jalon puis le bascule atteint/à venir', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')->post(route('admin.project.milestones.store'), [
        'edition_id' => $edition->id, 'title' => 'Publication des actes', 'due_date' => now()->addMonths(6)->toDateString(),
    ])->assertSessionHasNoErrors();

    $ms = ProjectMilestone::where('edition_id', $edition->id)->first();
    expect($ms->isDone())->toBeFalse();

    $this->actingAs($staff, 'web')->post(route('admin.project.milestones.toggle', $ms));
    expect($ms->fresh()->status)->toBe('done');
    expect($ms->fresh()->done_at)->not->toBeNull();

    $this->actingAs($staff, 'web')->post(route('admin.project.milestones.toggle', $ms));
    expect($ms->fresh()->status)->toBe('pending');
});
