<?php

use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\User;

it('affiche le hub Projet d’une édition', function () {
    $staff = adminStaff();
    $edition = openEdition();

    $this->actingAs($staff, 'web')
        ->get(route('admin.project.index', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('piloté comme un projet')
        ->assertSee('Initialiser le projet'); // projet vierge
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

    // La vue peuplée (kanban + liste + jalons) se rend sans erreur.
    $this->actingAs($staff, 'web')
        ->get(route('admin.project.index', ['edition_id' => $edition->id]))
        ->assertOk()
        ->assertSee('Avancement global')
        ->assertSee('Clôture des soumissions')
        ->assertSee('Calendrier')
        ->assertSee('projectCalendar', false); // le conteneur du calendrier des échéances
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

it('n’affecte un responsable que s’il appartient à l’équipe de l’édition', function () {
    $staff = adminStaff();
    $edition = openEdition();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $edition->team()->attach($member->id, ['role' => 'secretariat']);

    // Membre de l'équipe → accepté.
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'T1', 'status' => 'todo', 'priority' => 'normal',
        'assignee_id' => $member->id,
    ]);
    expect(ProjectTask::where('title', 'T1')->value('assignee_id'))->toBe($member->id);

    // Hors équipe → ignoré (null).
    $this->actingAs($staff, 'web')->post(route('admin.project.tasks.store'), [
        'edition_id' => $edition->id, 'title' => 'T2', 'status' => 'todo', 'priority' => 'normal',
        'assignee_id' => $outsider->id,
    ]);
    expect(ProjectTask::where('title', 'T2')->value('assignee_id'))->toBeNull();
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
