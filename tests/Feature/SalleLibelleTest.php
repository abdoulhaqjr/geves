<?php

use App\Models\Slot;
use Illuminate\Support\Str;

/**
 * **La salle ne se préfixe pas : la valeur nomme déjà le lieu.**
 *
 * `slots.room` et `sessions.room` sont saisis en toutes lettres - « Salle 5 »,
 * mais aussi « Auditorium », « Auditorium B », « Grand amphithéâtre ». Trois
 * écrans ajoutaient le mot « Salle » devant, et affichaient « Salle : Salle 5 »
 * ou « Salle Grand amphithéâtre ». Un cas par écran, parce que la règle se perd
 * écran par écran et que rien ne la rappelait.
 */
it('n’écrit pas « Salle : Salle A » sur le déroulement public', function () {
    $event = openEdition();
    $event->update([
        'title' => 'Colloque en direct',
        'date_start' => now()->subDay(),
        'date_end' => now()->addDay(),
    ]);
    $atelier = $event->ateliers()->create(['title' => 'Atelier 1', 'ordre' => 1]);
    $event->sessions()->create([
        'atelier_id' => $atelier->id, 'type' => 'atelier', 'label' => 'Séance du matin',
        'room' => 'Salle A', 'session_day' => now()->toDateString(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'ordre' => 1,
    ]);

    $this->get(route('public.deroulement'))
        ->assertOk()
        ->assertSee('Salle A')
        ->assertDontSee('Salle : Salle A');
});

it('n’écrit pas « Salle Auditorium » sur la fiche de communication', function () {
    // ⚠️ « Auditorium » plutôt que « Salle 5 » : c'est la valeur qui montre que
    // le préfixe est faux sur le FOND, pas seulement redondant.
    $event = openEdition();
    $account = verifiedAccount();
    $participant = $event->participants()->create([
        'user_id' => $account->id, 'first_name' => 'Awa', 'last_name' => 'Kodjo',
        'email' => $account->email, 'role' => 'author',
    ]);
    $submission = $event->submissions()->create([
        'participant_id' => $participant->id, 'ref_code' => 'UK-010',
        'title' => 'Une communication', 'status' => 'accepted',
        'revision_of' => 0, 'track' => 'Sciences', 'type' => 'oral',
        'access_token' => Str::random(64),
    ]);
    $session = $event->sessions()->create([
        'type' => 'atelier', 'label' => 'Séance - Atelier 1', 'room' => 'Auditorium',
        'session_day' => now()->toDateString(), 'ordre' => 1,
        'starts_at' => now()->addDay(),
    ]);
    Slot::create([
        'event_id' => $event->id, 'submission_id' => $submission->id,
        'participant_id' => $participant->id, 'session_id' => $session->id,
        'room' => 'Auditorium',
        'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(20),
    ]);

    $this->actingAs($account, 'account')
        ->get(route('account.submissions.show', $submission))
        ->assertOk()
        ->assertSee('Auditorium')
        ->assertDontSee('Salle Auditorium');
});
