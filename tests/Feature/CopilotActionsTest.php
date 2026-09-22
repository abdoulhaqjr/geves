<?php

use App\Actions\AskAdmin;
use App\Mail\PaymentReminderMail;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// openEdition() / adminStaff() : helpers partagés (tests/Pest.php) ;
// fakeAiProvider() / bindFakeAi() : GenerateReportTest.

/** Inscrit avec communication acceptée et frais non réglés (cible d'une relance). */
function unpaidAcceptedParticipant($edition): Participant
{
    $p = $edition->participants()->create([
        'first_name' => 'Ama', 'last_name' => 'Dette', 'email' => 'ama.dette@example.tg', 'role' => 'author',
    ]);
    $edition->submissions()->create([
        'participant_id' => $p->id, 'title' => 'Communication', 'status' => 'accepted', 'revision_of' => 0,
        'ref_code' => 'CP-1', 'access_token' => Str::random(64),
        'authors' => [['name' => 'Ama Dette', 'email' => 'ama.dette@example.tg', 'corresponding' => true]],
    ]);

    return $p;
}

it('exécute l’action de relance de paiement après autorisation', function () {
    Mail::fake();
    $edition = openEdition();
    $p = unpaidAcceptedParticipant($edition);

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.execute'), ['key' => 'send_payment_reminders', 'edition_id' => $edition->id])
        ->assertOk()
        ->assertJsonPath('entry.label', 'Exécutée');

    Mail::assertQueued(PaymentReminderMail::class);
    expect($p->fresh()->meta['payment_reminded_at'] ?? null)->not->toBeNull();
});

it('refuse d’exécuter une action hors de la capacité de l’utilisateur', function () {
    $edition = openEdition();
    $staff = User::create(['first_name' => 'Sam', 'last_name' => 'Support', 'email' => 'sam@example.tg', 'password' => 'pass12345']);
    $staff->assignedEditions()->attach($edition->id, ['role' => 'support']); // pas de capacité « payments »

    $this->actingAs($staff, 'web')
        ->postJson(route('admin.ai.execute'), ['key' => 'send_payment_reminders', 'edition_id' => $edition->id])
        ->assertStatus(403);
});

it('rejette une clé d’action inconnue', function () {
    $edition = openEdition();

    $this->actingAs(adminStaff(), 'web')
        ->postJson(route('admin.ai.execute'), ['key' => 'rm_-rf', 'edition_id' => $edition->id])
        ->assertStatus(403);
});

it('ne retient l’action proposée par l’IA que si l’utilisateur peut l’exécuter', function () {
    bindFakeAi(fakeAiProvider([
        'answer' => 'Je vais relancer les paiements en attente.',
        'action' => ['key' => 'send_payment_reminders', 'params' => []],
    ]));

    $edition = openEdition();

    // Utilisateur capable (super-admin) → action retenue.
    $ok = app(AskAdmin::class)->handle([], 'Relance les paiements', $edition, adminStaff());
    expect($ok['action'])->not->toBeNull();
    expect($ok['action']['key'])->toBe('send_payment_reminders');

    // Utilisateur sans la capacité → action ignorée (garde-fou côté serveur).
    $support = User::create(['first_name' => 'Sam', 'last_name' => 'Sup', 'email' => 'sup@example.tg', 'password' => 'pass12345']);
    $support->assignedEditions()->attach($edition->id, ['role' => 'support']);
    $ko = app(AskAdmin::class)->handle([], 'Relance les paiements', $edition, $support);
    expect($ko['action'])->toBeNull();
});
