<?php

use App\Mail\ParticipantInvitationMail;
use App\Models\Event;
use App\Models\Participant;
use App\Models\Submission;
use App\Support\EventParticipantRoster;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('liste les participants d’un événement avec rôle et paiement', function () {
    $staff = adminStaff();
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $event->payments()->create(['participant_id' => $p->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs($staff, 'web')
        ->get(route('admin.participants.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertSee('Awa KODJO')
        ->assertSee('Communicant')
        ->assertSee('Réglé');
});

it('invite un participant et le marque invité', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = openEdition();
    $p = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'attendee']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invite', $p))
        ->assertRedirect();

    Mail::assertQueued(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('awa@example.tg'));
    expect($p->fresh()->isInvited())->toBeTrue();
});

it('invite en masse les payés non invités uniquement', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = openEdition();

    $paid = $event->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'author']);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $unpaid = $event->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'author']);

    $alreadyInvited = $event->participants()->create(['first_name' => 'C', 'last_name' => 'Invité', 'email' => 'inv@example.tg', 'role' => 'author', 'meta' => ['invited_at' => now()->toDateTimeString()]]);
    $event->payments()->create(['participant_id' => $alreadyInvited->id, 'amount' => 15000, 'status' => 'paid']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invite-bulk'), ['event_id' => (string) $event->id])
        ->assertRedirect();

    // Seul le payé non invité reçoit l'e-mail.
    Mail::assertQueued(ParticipantInvitationMail::class, 1);
    Mail::assertQueued(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('paid@example.tg'));
    expect($paid->fresh()->isInvited())->toBeTrue();
    expect($unpaid->fresh()->isInvited())->toBeFalse();
});

/** Une communication déposée POUR UN TIERS : le déposant n'en est pas l'auteur. */
function deposePourUnTiers(Event $event, Participant $deposant, string $ref, array $correspondant): Submission
{
    $submission = $event->submissions()->create([
        'participant_id' => $deposant->id,
        'ref_code' => $ref,
        'title' => 'Communication '.$ref,
        'abstract' => str_repeat('Contexte. ', 10),
        'type' => 'oral',
        'status' => 'submitted',
        'authors' => [$correspondant + ['corresponding' => true, 'is_submitter' => false]],
    ]);

    $submission->authorLinks()->create([
        'event_id' => $event->id,
        'role' => 'corresponding',
        'author_index' => 0,
        'name' => $correspondant['name'],
        'email' => $correspondant['email'],
        'token' => Str::random(48),
        'status' => 'invited',
    ]);

    return $submission;
}

it('n’inscrit pas le compte qui a seulement déposé pour un tiers', function () {
    $staff = adminStaff();
    $event = openEdition();

    // Le compte de l'administration dépose le résumé de quelqu'un d'autre : il
    // n'est ni auteur correspondant, ni co-auteur, ni payeur.
    $deposant = $event->participants()->create([
        'first_name' => 'Admin', 'last_name' => 'UK', 'email' => 'devs@univkara.tg', 'role' => 'author',
    ]);
    deposePourUnTiers($event, $deposant, '26-050', [
        'name' => 'DANAME Marcelle', 'email' => 'marcelle@example.tg',
    ]);

    $registre = app(EventParticipantRoster::class)->forEvent((string) $event->id);

    expect($registre->pluck('email')->all())->not->toContain('devs@univkara.tg');

    $this->actingAs($staff, 'web')
        ->get(route('admin.participants.index', ['event_id' => $event->id]))
        ->assertOk()
        ->assertDontSee('devs@univkara.tg')
        ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 0 && $stats['communicants'] === 0);
});

it('inscrit le déposant quand il est co-auteur d’une autre communication', function () {
    $event = openEdition();
    $registre = fn () => app(EventParticipantRoster::class)->forEvent((string) $event->id);

    $deposant = $event->participants()->create([
        'first_name' => 'Admin', 'last_name' => 'UK', 'email' => 'devs@univkara.tg', 'role' => 'author',
    ]);
    deposePourUnTiers($event, $deposant, '26-050', ['name' => 'DANAME Marcelle', 'email' => 'marcelle@example.tg']);
    expect($registre()->pluck('email')->all())->not->toContain('devs@univkara.tg');

    // 1. Co-auteur d'une AUTRE communication : il revient, en co-auteur.
    deposePourUnTiers($event, $deposant, '26-051', ['name' => 'KODJO Awa', 'email' => 'awa@example.tg'])
        ->authorLinks()->create([
            'event_id' => $event->id, 'role' => 'coauthor', 'author_index' => 1,
            'name' => 'Admin UK', 'email' => 'devs@univkara.tg',
            'token' => Str::random(48), 'status' => 'linked',
        ]);

    expect($registre()->firstWhere('email', 'devs@univkara.tg'))->not->toBeNull()
        ->and($registre()->firstWhere('email', 'devs@univkara.tg')->role)->toBe('coauthor');
});

it('garde le déposant comme auteur correspondant quand la communication le dit', function () {
    $event = openEdition();

    // Le cas ordinaire, et celui des imports : le premier auteur porte l'adresse
    // du déposant. Retirer la promotion d'office ne doit pas le faire disparaître.
    $deposant = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    deposePourUnTiers($event, $deposant, '26-052', ['name' => 'KODJO Awa', 'email' => 'awa@example.tg']);

    $ligne = app(EventParticipantRoster::class)->forEvent((string) $event->id)
        ->firstWhere('email', 'awa@example.tg');

    expect($ligne)->not->toBeNull()->and($ligne->role)->toBe('author');
});

it('ne transforme pas le déposant en auteur si la communication ne désigne personne', function () {
    $event = openEdition();

    $deposant = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $event->submissions()->create([
        'participant_id' => $deposant->id, 'ref_code' => '26-053', 'title' => 'Sans auteur nommé',
        'abstract' => str_repeat('Contexte. ', 10), 'type' => 'oral', 'status' => 'submitted',
        'authors' => [['name' => 'KODJO Awa', 'is_submitter' => false]],
    ]);

    $ligne = app(EventParticipantRoster::class)->forEvent((string) $event->id)
        ->firstWhere('email', 'awa@example.tg');

    expect($ligne)->toBeNull();
});

it('reconnaît un auteur correspondant explicitement marqué comme déposant même sans e-mail', function () {
    $event = openEdition();
    $deposant = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author',
    ]);
    $event->submissions()->create([
        'participant_id' => $deposant->id, 'ref_code' => '26-054', 'title' => 'Auteur sans e-mail',
        'abstract' => str_repeat('Contexte. ', 10), 'type' => 'oral', 'status' => 'submitted',
        'authors' => [['name' => 'KODJO Awa', 'is_submitter' => true, 'corresponding' => true]],
    ]);

    $ligne = app(EventParticipantRoster::class)->forEvent((string) $event->id)
        ->firstWhere('email', 'awa@example.tg');

    expect($ligne)->not->toBeNull()->and($ligne->role)->toBe('author');
});

it('inscrit le déposant comme auditeur simple seulement après un paiement de participation', function () {
    $event = openEdition();
    $deposant = $event->participants()->create([
        'first_name' => 'Admin', 'last_name' => 'UK', 'email' => 'devs@univkara.tg', 'role' => 'attendee',
    ]);
    deposePourUnTiers($event, $deposant, '26-055', [
        'name' => 'DANAME Marcelle', 'email' => 'marcelle@example.tg',
    ]);

    $registre = fn () => app(EventParticipantRoster::class)->forEvent((string) $event->id);
    expect($registre()->firstWhere('email', 'devs@univkara.tg'))->toBeNull();

    $event->payments()->create([
        'participant_id' => $deposant->id, 'amount' => 15000, 'status' => 'paid',
    ]);

    $ligne = $registre()->firstWhere('email', 'devs@univkara.tg');
    expect($ligne)->not->toBeNull()->and($ligne->role)->toBe('attendee')
        ->and($ligne->paid)->toBeTrue();
});
