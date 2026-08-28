<?php

use App\Actions\QueueEmailCampaign;
use App\Actions\SendDueEmails;
use App\Mail\CampaignMail;
use App\Models\Edition;
use App\Models\Email;
use App\Support\EmailAudience;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

function audienceEdition(): Edition
{
    $edition = openEdition();

    $paid = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'author']);
    $edition->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);

    $edition->participants()->create(['first_name' => 'Ben', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'attendee']);

    $reviewer = $edition->participants()->create(['first_name' => 'Rita', 'last_name' => 'Relecteur', 'email' => 'reviewer@example.tg', 'role' => 'reviewer']);

    $author = $edition->participants()->create(['first_name' => 'Coco', 'last_name' => 'Accepté', 'email' => 'accepted@example.tg', 'role' => 'author']);
    $edition->submissions()->create(['participant_id' => $author->id, 'title' => 'Titre', 'status' => 'accepted', 'revision_of' => 0, 'ref_code' => 'AA-001', 'access_token' => Str::random(64)]);

    // Sans e-mail : jamais destinataire.
    $edition->participants()->create(['first_name' => 'Sans', 'last_name' => 'Mail', 'email' => '', 'role' => 'attendee']);

    return $edition;
}

it('résout les audiences selon le profil', function () {
    $edition = audienceEdition();

    expect(EmailAudience::resolve($edition, 'all')->pluck('email')->sort()->values()->all())
        ->toBe(['accepted@example.tg', 'paid@example.tg', 'reviewer@example.tg', 'unpaid@example.tg']);
    expect(EmailAudience::resolve($edition, 'paid')->pluck('email')->all())->toBe(['paid@example.tg']);
    expect(EmailAudience::resolve($edition, 'reviewers')->pluck('email')->all())->toBe(['reviewer@example.tg']);
    expect(EmailAudience::resolve($edition, 'accepted')->pluck('email')->all())->toBe(['accepted@example.tg']);
    expect(EmailAudience::resolve($edition, 'unpaid')->pluck('email')->sort()->values()->all())
        ->toBe(['accepted@example.tg', 'reviewer@example.tg', 'unpaid@example.tg']);
});

it('met une campagne immédiate en file et l’envoie tout de suite', function () {
    Mail::fake();
    $staff = adminStaff();
    $edition = audienceEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.emails.send'), [
            'edition_id' => (string) $edition->id,
            'audience' => 'paid',
            'subject' => 'Bonjour {prenom}',
            'body' => 'Merci {nom_complet} pour {edition}.',
        ])
        ->assertSessionHasNoErrors();

    Mail::assertSent(CampaignMail::class, 1);
    $email = Email::first();
    expect($email->status)->toBe('sent');
    expect($email->recipient)->toBe('paid@example.tg');
    expect($email->subject)->toBe('Bonjour Awa');            // {prenom} personnalisé
    expect($email->body)->toContain('Awa Payé');             // {nom_complet}
    expect($email->sent_at)->not->toBeNull();
});

it('programme une campagne future sans l’envoyer avant l’échéance', function () {
    Mail::fake();
    $edition = audienceEdition();

    $count = app(QueueEmailCampaign::class)->handle(
        $edition, 'reviewers', 'Rappel', 'Corps', now()->addDay()
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
    $edition = audienceEdition();

    app(QueueEmailCampaign::class)->handle($edition, 'reviewers', 'Rappel', 'Corps', now()->subMinute());

    $report = app(SendDueEmails::class)->handle();

    expect($report['sent'])->toBe(1);
    Mail::assertSent(CampaignMail::class, 1);
    expect(Email::first()->status)->toBe('sent');
});

it('n’envoie pas deux fois le même e-mail programmé (idempotence)', function () {
    Mail::fake();
    $edition = audienceEdition();

    app(QueueEmailCampaign::class)->handle($edition, 'paid', 'Rappel', 'Corps', now()->subMinute());
    app(SendDueEmails::class)->handle();
    app(SendDueEmails::class)->handle();

    Mail::assertSent(CampaignMail::class, 1);
});

it('la commande emails:send vide la file arrivée à échéance', function () {
    Mail::fake();
    $edition = audienceEdition();
    app(QueueEmailCampaign::class)->handle($edition, 'all', 'Rappel', 'Corps', now()->subMinute());

    $this->artisan('emails:send')->assertSuccessful();

    expect(Email::where('status', 'sent')->count())->toBe(4);
    Mail::assertSent(CampaignMail::class, 4);
});

it('annule un e-mail encore en file', function () {
    $staff = adminStaff();
    $edition = audienceEdition();
    app(QueueEmailCampaign::class)->handle($edition, 'paid', 'Rappel', 'Corps', now()->addDay());
    $email = Email::first();

    $this->actingAs($staff, 'web')->delete(route('admin.emails.cancel', $email));

    expect(Email::find($email->id))->toBeNull();
});

it('refuse une audience sans destinataire', function () {
    $staff = adminStaff();
    $edition = openEdition(); // aucune participation

    $this->actingAs($staff, 'web')
        ->post(route('admin.emails.send'), [
            'edition_id' => (string) $edition->id,
            'audience' => 'paid',
            'subject' => 'Objet',
            'body' => 'Corps',
        ]);

    expect(Email::count())->toBe(0);
});
