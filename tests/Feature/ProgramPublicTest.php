<?php

use App\Models\User;

// adminStaff() -> AdminSubmissionWorkflowTest ; seededSubmission() -> idem

function scheduledEdition(): array
{
    $submission = seededSubmission('accepted'); // édition ouverte « uk-2026 », comm. « Innovation durable »
    $edition = $submission->edition;

    $plenary = $edition->sessions()->create(['type' => 'plenary', 'label' => 'Cérémonie d’ouverture', 'ordre' => 0, 'room' => 'Amphi A']);
    $atelier = $edition->ateliers()->create(['title' => 'Atelier Santé', 'tracks' => ['Santé'], 'ordre' => 0]);
    $aSession = $edition->sessions()->create(['type' => 'atelier', 'atelier_id' => $atelier->id, 'label' => 'Session parallèle 1', 'ordre' => 1]);
    $edition->slots()->create([
        'session_id' => $aSession->id,
        'submission_id' => $submission->id,
        'participant_id' => $submission->participant_id,
    ]);

    return [$edition, $submission];
}

it('affiche le programme public (sessions plénières, ateliers, communications)', function () {
    [$edition, $submission] = scheduledEdition();

    $this->get(route('public.colloque', $edition->slug))
        ->assertOk()
        ->assertSee('Programme')
        ->assertSee('Cérémonie d’ouverture', false)
        ->assertSee('Atelier Santé')
        ->assertSee('Session parallèle 1')
        ->assertSee('Innovation durable');
});

it('n’affiche pas de programme quand il n’y a aucune session', function () {
    $submission = seededSubmission('accepted');

    $this->get(route('public.colloque', $submission->edition->slug))
        ->assertOk()
        ->assertDontSee('>Programme<', false);
});

it('génère les fiches de session imprimables', function () {
    $staff = adminStaff();
    [$edition, $submission] = scheduledEdition();

    $this->actingAs($staff, 'web')->get(route('admin.documents.sessions', $edition))
        ->assertOk()
        ->assertSee('Fiches de session')
        ->assertSee('Session parallèle 1')
        ->assertSee('Innovation durable')
        ->assertSee('Cérémonie d’ouverture', false);
});

it('protège les fiches de session sans permission', function () {
    [$edition] = scheduledEdition();
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get(route('admin.documents.sessions', $edition))->assertForbidden();
});
