<?php

use App\Actions\QueueEmailCampaign;
use App\Actions\SendDueEmails;
use App\Mail\CampaignMail;
use App\Models\Email;
use App\Models\Event;
use App\Support\EmailAudience;
use App\Support\Settings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

function audienceEdition(): Event
{
    $event = openEdition();

    $paid = $event->participants()->create(['first_name' => 'Awa', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'author']);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $event->participants()->create(['first_name' => 'Ben', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'attendee']);

    $reviewer = $event->participants()->create(['first_name' => 'Rita', 'last_name' => 'Relecteur', 'email' => 'reviewer@example.tg', 'role' => 'reviewer']);

    $author = $event->participants()->create(['first_name' => 'Coco', 'last_name' => 'Accepté', 'email' => 'accepted@example.tg', 'role' => 'author']);
    $event->submissions()->create(['participant_id' => $author->id, 'title' => 'Titre', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);

    // Sans e-mail : jamais destinataire.
    $event->participants()->create(['first_name' => 'Sans', 'last_name' => 'Mail', 'email' => '', 'role' => 'attendee']);

    return $event;
}

it('résout les audiences selon le profil', function () {
    $event = audienceEdition();

    expect(EmailAudience::resolve($event, 'all')->pluck('email')->sort()->values()->all())
        ->toBe(['accepted@example.tg', 'paid@example.tg', 'reviewer@example.tg', 'unpaid@example.tg']);
    expect(EmailAudience::resolve($event, 'paid')->pluck('email')->all())->toBe(['paid@example.tg']);
    expect(EmailAudience::resolve($event, 'reviewers')->pluck('email')->all())->toBe(['reviewer@example.tg']);
    expect(EmailAudience::resolve($event, 'accepted')->pluck('email')->all())->toBe(['accepted@example.tg']);
    expect(EmailAudience::resolve($event, 'unpaid')->pluck('email')->sort()->values()->all())
        ->toBe(['accepted@example.tg', 'reviewer@example.tg', 'unpaid@example.tg']);
});

it('met une campagne immédiate en file et l’envoie tout de suite', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = audienceEdition();

    // Personnalise le modèle e-mail utilisé par la campagne.
    Settings::set('email_templates', [
        'participant_invitation' => [
            'subject' => 'Bonjour {prenom}',
            'body' => 'Merci {nom_complet} pour {event}.',
            'active' => true,
        ],
    ]);

    $this->actingAs($staff, 'web')
        ->post(route('admin.emails.send'), [
            'event_id' => (string) $event->id,
            'template' => 'participant_invitation',
            'audience' => 'paid',
        ])
        ->assertSessionHasNoErrors();

    Mail::assertSent(CampaignMail::class, 1);
    $email = Email::first();
    expect($email->status)->toBe('sent');
    expect($email->recipient)->toBe('paid@example.tg');
    expect($email->subject)->toBe('Bonjour Awa');            // {prenom} personnalisé
    expect($email->body)->toContain('Awa PAYÉ');             // {nom_complet}
    expect($email->sent_at)->not->toBeNull();
});

it('programme une campagne future sans l’envoyer avant l’échéance', function () {
    Mail::fake();
    $event = audienceEdition();

    $count = app(QueueEmailCampaign::class)->handle(
        $event, 'reviewers', 'Rappel', 'Corps', now()->addDay()
    );

    expect($count)->toBe(1);
    Mail::assertNothingSent();
    expect(Email::where('status', 'queued')->count())->toBe(1);

    // Rien n'est dû aujourd'hui.
    $report = app(SendDueEmails::class)->handle();
    expect($report['sent'])->toBe(0);
    Mail::assertNothingSent();
});

it('envoie les e-mails programmés une fois l’échéance passée', function () {
    Mail::fake();
    $event = audienceEdition();

    app(QueueEmailCampaign::class)->handle($event, 'reviewers', 'Rappel', 'Corps', now()->subMinute());

    $report = app(SendDueEmails::class)->handle();

    expect($report['sent'])->toBe(1);
    Mail::assertSent(CampaignMail::class, 1);
    expect(Email::first()->status)->toBe('sent');
});

it('n’envoie pas deux fois le même e-mail programmé (idempotence)', function () {
    Mail::fake();
    $event = audienceEdition();

    app(QueueEmailCampaign::class)->handle($event, 'paid', 'Rappel', 'Corps', now()->subMinute());
    app(SendDueEmails::class)->handle();
    app(SendDueEmails::class)->handle();

    Mail::assertSent(CampaignMail::class, 1);
});

it('la commande emails:send vide la file arrivée à échéance', function () {
    Mail::fake();
    $event = audienceEdition();
    app(QueueEmailCampaign::class)->handle($event, 'all', 'Rappel', 'Corps', now()->subMinute());

    $this->artisan('emails:send')->assertSuccessful();

    expect(Email::where('status', 'sent')->count())->toBe(4);
    Mail::assertSent(CampaignMail::class, 4);
});

it('annule un e-mail encore en file', function () {
    $staff = adminStaff();
    $event = audienceEdition();
    app(QueueEmailCampaign::class)->handle($event, 'paid', 'Rappel', 'Corps', now()->addDay());
    $email = Email::first();

    $this->actingAs($staff, 'web')->delete(route('admin.emails.cancel', $email));

    expect(Email::find($email->id))->toBeNull();
});

it('refuse une audience sans destinataire', function () {
    $staff = adminStaff();
    $event = openEdition(); // aucune participation

    $this->actingAs($staff, 'web')
        ->post(route('admin.emails.send'), [
            'event_id' => (string) $event->id,
            'template' => 'participant_invitation',
            'audience' => 'paid',
        ]);

    expect(Email::count())->toBe(0);
});

it('regroupe une campagne (batch_id + audience) et l’annule par lot', function () {
    $staff = adminStaff();
    $event = audienceEdition();

    // Programme une campagne à l'audience « payés » → e-mails en file avec batch_id.
    $this->actingAs($staff, 'web')->post(route('admin.emails.send'), [
        'event_id' => (string) $event->id,
        'template' => 'participant_invitation',
        'audience' => 'all',
        'date' => now()->addDays(3)->toDateString(),
        'heure' => '09:00',
    ])->assertSessionHasNoErrors();

    $batch = Email::whereNotNull('batch_id')->first();
    expect($batch)->not->toBeNull();
    expect($batch->audience)->toBe('all');
    expect(Email::where('batch_id', $batch->batch_id)->where('status', 'queued')->count())->toBeGreaterThan(0);

    // Annulation par lot : toute la campagne en file est retirée.
    $this->actingAs($staff, 'web')->post(route('admin.emails.cancel-batch'), ['batch_id' => $batch->batch_id])
        ->assertSessionHasNoErrors();

    expect(Email::where('batch_id', $batch->batch_id)->where('status', 'queued')->count())->toBe(0);
});
