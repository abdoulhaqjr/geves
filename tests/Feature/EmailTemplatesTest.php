<?php

use App\Mail\ParticipantInvitationMail;
use App\Mail\SubmissionStatusMail;
use App\Support\EmailTemplates;
use App\Support\Settings;
use Illuminate\Support\Str;

// adminStaff() -> AdminSubmissionWorkflowTest ; openEdition() -> AccountSubmissionTest

it('utilise les valeurs par défaut sans personnalisation', function () {
    $tpl = EmailTemplates::raw('submission_accepted');

    expect($tpl['subject'])->toBe('Votre communication est acceptée');
    expect($tpl['heading'])->toBe('Communication acceptée');
});

it('applique une personnalisation avec placeholders sur une soumission', function () {
    Settings::set('email_templates', [
        'submission_accepted' => [
            'subject' => 'Bravo {prenom} !',
            'heading' => 'Communication {ref} acceptée',
            'intro' => 'Félicitations, « {titre} » est retenue pour {edition}.',
        ],
    ]);

    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $submission = $edition->submissions()->create([
        'participant_id' => $p->id, 'title' => 'Les colloques', 'status' => 'accepted', 'revision_of' => 0,
        'ref_code' => 'AA-001', 'access_token' => Str::random(64),
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);

    $meta = EmailTemplates::forSubmission($submission, 'accepted');

    expect($meta['subject'])->toBe('Bravo Awa !');
    expect($meta['heading'])->toBe('Communication AA-001 acceptée');
    expect($meta['intro'])->toBe('Félicitations, « Les colloques » est retenue pour UK 2026.');
});

it('propage le modèle personnalisé au Mailable de statut', function () {
    Settings::set('email_templates', [
        'submission_accepted' => ['subject' => 'Bravo {prenom} !', 'heading' => 'Youpi', 'intro' => 'Bien joué.'],
    ]);

    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);
    $submission = $edition->submissions()->create([
        'participant_id' => $p->id, 'title' => 'T', 'status' => 'accepted', 'revision_of' => 0,
        'ref_code' => 'AA-001', 'access_token' => Str::random(64),
        'authors' => [['name' => 'Awa Kodjo', 'email' => 'awa@example.tg', 'corresponding' => true]],
    ]);

    $mail = new SubmissionStatusMail($submission, 'accepted');

    $mail->assertHasSubject('Bravo Awa !');
    $mail->assertSeeInHtml('Youpi');
    $mail->assertSeeInHtml('Bien joué.');
});

it('personnalise l’invitation participant', function () {
    Settings::set('email_templates', [
        'participant_invitation' => ['subject' => 'Bienvenue', 'heading' => 'Salut {prenom}', 'intro' => 'À bientôt à {edition}.'],
    ]);

    $edition = openEdition();
    $p = $edition->participants()->create(['first_name' => 'Awa', 'last_name' => 'Kodjo', 'email' => 'awa@example.tg', 'role' => 'author']);

    $mail = new ParticipantInvitationMail($p);

    $mail->assertHasSubject('Bienvenue - UK 2026');
    $mail->assertSeeInHtml('Salut Awa');
    $mail->assertSeeInHtml('À bientôt à UK 2026.');
});

it('enregistre les modèles depuis le back-office', function () {
    $staff = adminStaff();

    $this->actingAs($staff, 'web')
        ->put(route('admin.emails.templates.update'), [
            'templates' => ['submission_accepted' => ['subject' => 'Sujet personnalisé']],
        ])
        ->assertSessionHasNoErrors();

    expect(EmailTemplates::raw('submission_accepted')['subject'])->toBe('Sujet personnalisé');
});

it('affiche la valeur courante du modèle dans l’onglet Modèles', function () {
    $staff = adminStaff();
    Settings::set('email_templates', ['submission_accepted' => ['subject' => 'Autre chose', 'heading' => '', 'intro' => '']]);

    $this->actingAs($staff, 'web')
        ->get(route('admin.emails.index'))
        ->assertOk()
        ->assertSee('Autre chose'); // objet courant listé dans l'onglet « Modèles »
});

it('refuse l’édition des modèles aux invités', function () {
    $this->put(route('admin.emails.templates.update'))->assertRedirect(route('account.login'));
});
