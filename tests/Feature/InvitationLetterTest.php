<?php

use App\Documents\InvitationLetter;
use App\Documents\InvitationVars;
use App\Documents\LayoutSanitizer;
use App\Mail\ParticipantInvitationMail;
use App\Support\EventSettings;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('dessine une lettre d’invitation portrait, sans QR ni identifiant', function () {
    $layout = LayoutSanitizer::default('attestation', 'invitation');

    // A4 portrait (contrairement aux attestations, en paysage).
    expect($layout['width'])->toBe(794)
        ->and($layout['height'])->toBe(1123);

    $types = collect($layout['elements'])->pluck('type');
    // Une invitation convie, elle n'atteste pas : pas de QR de vérification.
    expect($types)->not->toContain('qr')
        ->and($types->filter(fn ($t) => $t === 'image')->count())->toBe(2); // les deux logos

    // Les jetons attendus figurent dans le corps.
    $texts = collect($layout['elements'])->pluck('text')->filter()->implode(' ');
    expect($texts)->toContain('[recipient.civility] [recipient.name]')
        ->toContain('[edition.name]')
        ->toContain('[edition.dates]')
        ->toContain('[signatory.name]');
});

it('remplit les valeurs de fusion depuis le participant et l’événement', function () {
    $event = openEdition();
    $event->update(['location' => 'Kara, Togo', 'theme' => 'Innover pour un avenir durable']);
    EventSettings::set($event, 'attestations.signatory', 'Pr. Padabo Kadouza');
    EventSettings::set($event, 'attestations.signatory_role', 'Président du comité d’organisation');

    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg',
        'institution' => 'Université de Kara', 'role' => 'attendee',
    ]);

    $vars = InvitationVars::for($p->fresh());

    expect($vars['name'])->toContain('KODJO')
        ->and($vars['event'])->toBe('UK 2026')
        ->and($vars['institution'])->toBe('Université de Kara')
        ->and($vars['theme'])->toBe('Innover pour un avenir durable')
        ->and($vars['signatory'])->toBe('Pr. Padabo Kadouza')
        ->and($vars['signatory_role'])->toBe('Président du comité d’organisation');
});

it('génère le PDF de la lettre d’invitation depuis le modèle', function () {
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'attendee',
    ]);

    $bytes = InvitationLetter::pdf($p->fresh())->output();

    expect(substr($bytes, 0, 4))->toBe('%PDF');
});

it('joint la lettre PDF à l’e-mail d’invitation', function () {
    $event = openEdition();
    $p = $event->participants()->create([
        'first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'attendee',
    ]);

    $attachments = (new ParticipantInvitationMail($p->fresh()))->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0])->toBeInstanceOf(Attachment::class);
});

it('invite en masse TOUS les non-invités par défaut', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = openEdition();

    $paid = $event->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'attendee']);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);
    $unpaid = $event->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'attendee']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invite-bulk'), ['event_id' => (string) $event->id])
        ->assertRedirect();

    // Payé ET impayé, non invités : les deux reçoivent l'invitation.
    Mail::assertQueued(ParticipantInvitationMail::class, 2);
    expect($paid->fresh()->isInvited())->toBeTrue()
        ->and($unpaid->fresh()->isInvited())->toBeTrue();
});

it('limite l’invitation groupée aux payés quand l’option est posée', function () {
    Mail::fake();
    $staff = adminStaff();
    $event = openEdition();
    EventSettings::set($event, 'invitation.restrict_paid', true);

    $paid = $event->participants()->create(['first_name' => 'A', 'last_name' => 'Payé', 'email' => 'paid@example.tg', 'role' => 'attendee']);
    $event->payments()->create(['participant_id' => $paid->id, 'amount' => 15000, 'status' => 'paid']);
    $unpaid = $event->participants()->create(['first_name' => 'B', 'last_name' => 'Impayé', 'email' => 'unpaid@example.tg', 'role' => 'attendee']);

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invite-bulk'), ['event_id' => (string) $event->id])
        ->assertRedirect();

    Mail::assertQueued(ParticipantInvitationMail::class, 1);
    Mail::assertQueued(ParticipantInvitationMail::class, fn ($m) => $m->hasTo('paid@example.tg'));
    expect($unpaid->fresh()->isInvited())->toBeFalse();
});

it('enregistre l’option « payés seulement » par événement', function () {
    $staff = adminStaff();
    $event = openEdition();

    $this->actingAs($staff, 'web')
        ->post(route('admin.participants.invitation-options'), ['event_id' => (string) $event->id, 'restrict_paid' => '1'])
        ->assertRedirect();

    expect((bool) EventSettings::get($event->fresh(), 'invitation.restrict_paid', false))->toBeTrue();
});
