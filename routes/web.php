<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LogoutController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\AttestationController;
use App\Http\Controllers\EgressController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\ColloqueController;
use App\Http\Controllers\Public\DeroulementController;
use App\Livewire\Account\Dashboard;
use App\Livewire\Account\Documents as AccountDocuments;
use App\Livewire\Account\ForgotPassword;
use App\Livewire\Account\Login;
use App\Livewire\Account\Payment\Pay as AccountPay;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Register;
use App\Livewire\Account\ResetPassword;
use App\Livewire\Account\Security as AccountSecurity;
use App\Livewire\Account\Submissions\Create as SubmissionCreate;
use App\Livewire\Account\Submissions\Index as SubmissionIndex;
use App\Livewire\Account\Submissions\Show as SubmissionShow;
use App\Livewire\Account\VerifyEmailNotice;
use App\Livewire\Admin\AiAssistant as AdminAiAssistant;
use App\Livewire\Admin\BadgeScan as AdminBadgeScan;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Documents\Attestations as AdminAttestations;
use App\Livewire\Admin\Documents\TemplateDesigner as AdminTemplateDesigner;
use App\Livewire\Admin\Editions\Documents as AdminEditionDocuments;
use App\Livewire\Admin\Editions\Form as EditionForm;
use App\Livewire\Admin\Editions\Index as EditionIndex;
use App\Livewire\Admin\Editions\SubmissionFormEditor;
use App\Livewire\Admin\Emails\Index as AdminEmailIndex;
use App\Livewire\Admin\Emails\Templates as AdminEmailTemplates;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Admin\Participants\Index as AdminParticipantIndex;
use App\Livewire\Admin\Participants\Preview as AdminParticipantPreview;
use App\Livewire\Admin\Payments\Index as AdminPaymentIndex;
use App\Livewire\Admin\Payments\Record as AdminPaymentRecord;
use App\Livewire\Admin\Program\Sessions as AdminProgramSessions;
use App\Livewire\Admin\Program\Slots as AdminProgramSlots;
use App\Livewire\Admin\Reports\Index as AdminReportIndex;
use App\Livewire\Admin\Settings\Agents as AdminSettingsAgents;
use App\Livewire\Admin\Settings\Index as AdminSettings;
use App\Livewire\Admin\Submissions\Index as AdminSubmissionIndex;
use App\Livewire\Admin\Submissions\Show as AdminSubmissionShow;
use App\Livewire\Notifications\Center as NotificationCenter;
use App\Livewire\Public\CoauthorPayment;
use App\Livewire\Public\GescoChat;
use App\Livewire\Reviewer\Evaluate as ReviewerEvaluate;
use App\Livewire\Reviewer\Portal as ReviewerPortal;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Page publique d'une édition de colloque.
Route::get('/deroulement', DeroulementController::class)->name('public.deroulement');
Route::get('/assistante', GescoChat::class)->name('public.gesco');

Route::get('/colloques-passes', [ArchiveController::class, 'index'])->name('public.archives');
Route::get('/colloques-passes/{slug}', [ArchiveController::class, 'show'])->name('public.archive');

Route::get('/colloque/{slug}', ColloqueController::class)->name('public.colloque');

// Parcours de paiement des co-auteurs (public, sans compte, retrouvé par e-mail).
Route::get('/colloque/{slug}/co-auteur', CoauthorPayment::class)->name('public.coauthor');

// Visio interactive d'une session (accès géré dans le contrôleur selon le rôle).
Route::get('/session/{session}/visio', [MeetingController::class, 'join'])->name('session.meeting');
// Enregistrement (LiveKit Egress) — réservé au modérateur (contrôle dans le contrôleur).
Route::post('/session/{session}/egress/start', [EgressController::class, 'start'])->name('session.egress.start');
Route::post('/session/{session}/egress/stop', [EgressController::class, 'stop'])->name('session.egress.stop');

// Portail relecteur (accès par jeton, sans compte).
Route::get('/relecture/{token}', ReviewerPortal::class)->name('reviewer.portal');
Route::get('/relecture/{token}/{review}', ReviewerEvaluate::class)->name('reviewer.evaluate');

// Attestations : document imprimable + vérification publique (par UUID).
Route::get('/attestation/{uuid}', [AttestationController::class, 'document'])->name('attestation.document');
Route::get('/attestation/{uuid}/pdf', [AttestationController::class, 'pdf'])->name('attestation.pdf');
Route::get('/verifier', [AttestationController::class, 'verifyLanding'])->name('attestation.verify.landing');
Route::get('/verifier/{uuid}', [AttestationController::class, 'verify'])->name('attestation.verify');

// Paiement en ligne : retour navigateur + webhook serveur-à-serveur.
Route::get('/paiement/retour/{gateway}', [PaymentController::class, 'return'])->name('payment.return');
Route::post('/webhooks/paiement/{gateway}', [PaymentController::class, 'webhook'])->name('payment.webhook');

/*
|--------------------------------------------------------------------------
| Comptes participants (guard « account »)
|--------------------------------------------------------------------------
*/
Route::middleware('guest:account')->group(function () {
    Route::get('/inscription', Register::class)->name('account.register');
    Route::get('/connexion', Login::class)->name('account.login');
    Route::get('/mot-de-passe-oublie', ForgotPassword::class)->name('account.password.request');
    Route::get('/reinitialiser/{token}', ResetPassword::class)->name('account.password.reset');
});

Route::middleware(['auth:account', \App\Http\Middleware\StampAccountSession::class])->group(function () {
    Route::get('/mon-compte', Dashboard::class)->name('account.dashboard');
    Route::get('/mon-profil', Profile::class)->name('account.profile');
    Route::get('/mes-documents', AccountDocuments::class)->name('account.documents');
    Route::get('/mes-notifications', NotificationCenter::class)->name('account.notifications');
    Route::get('/mon-compte/securite', AccountSecurity::class)->name('account.security');

    // Communications
    Route::get('/mes-communications', SubmissionIndex::class)->name('account.submissions.index');
    Route::get('/mes-communications/{submission}', SubmissionShow::class)->name('account.submissions.show');
    Route::get('/colloque/{slug}/soumettre', SubmissionCreate::class)->name('account.submissions.create');

    // Paiement des frais
    Route::get('/mon-compte/paiement/{edition}', AccountPay::class)->name('account.payment');
    Route::get('/mon-compte/recu/{payment}', App\Http\Controllers\Account\ReceiptController::class)->name('account.receipt');
    Route::get('/mon-compte/edition/{edition}/livret', [App\Http\Controllers\Account\DocumentController::class, 'livret'])->name('account.documents.livret');

    // Vérification d'e-mail
    Route::get('/verification-email', VerifyEmailNotice::class)->name('account.verification.notice');
    Route::get('/verifier-email/{id}/{hash}', EmailVerificationController::class)
        ->middleware('signed')
        ->name('account.verification.verify');

    Route::post('/deconnexion', LogoutController::class)->name('account.logout');

    // Quitter l'impersonation (le staff redevient lui-même).
    Route::post('/impersonation/quitter', [ImpersonationController::class, 'leave'])->name('impersonation.leave');
});

/*
|--------------------------------------------------------------------------
| Back-office (guard « web » + permission manage_colloques)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::middleware('guest:web')->group(function () {
        Route::get('/connexion', AdminLogin::class)->name('admin.login');
    });

    Route::middleware(['auth:web', 'can:manage_colloques'])->group(function () {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');
        Route::get('/notifications', NotificationCenter::class)->name('admin.notifications');

        Route::get('/editions', EditionIndex::class)->name('admin.editions.index');
        Route::get('/editions/creer', EditionForm::class)->name('admin.editions.create');
        Route::get('/editions/{edition}/modifier', EditionForm::class)->name('admin.editions.edit');
        Route::get('/editions/{edition}/formulaire', SubmissionFormEditor::class)->name('admin.editions.form');
        Route::get('/editions/{edition}/documents', AdminEditionDocuments::class)->name('admin.editions.documents');

        Route::get('/soumissions', AdminSubmissionIndex::class)->name('admin.submissions.index');
        Route::get('/soumissions/{submission}', AdminSubmissionShow::class)->name('admin.submissions.show');

        Route::get('/paiements', AdminPaymentIndex::class)->name('admin.payments.index');
        Route::get('/paiements/creer', AdminPaymentRecord::class)->name('admin.payments.create');
        Route::get('/paiements/{payment}/recu', ReceiptController::class)->name('admin.payments.receipt');

        Route::get('/participants', AdminParticipantIndex::class)->name('admin.participants.index');
        Route::get('/participants/{participant}/apercu', AdminParticipantPreview::class)->name('admin.participants.preview');
        Route::post('/participants/{participant}/impersonate', [ImpersonationController::class, 'take'])->name('admin.participants.impersonate');

        Route::get('/emails', AdminEmailIndex::class)->name('admin.emails.index');
        Route::get('/emails/modeles', AdminEmailTemplates::class)->name('admin.emails.templates');

        Route::get('/rapports', AdminReportIndex::class)->name('admin.reports.index');
        Route::get('/rapports/{edition}/excel', [ReportController::class, 'xlsx'])->name('admin.reports.xlsx');
        Route::get('/rapports/{edition}/pdf', [ReportController::class, 'pdf'])->name('admin.reports.pdf');

        Route::get('/programme', AdminProgramSessions::class)->name('admin.program.sessions');
        Route::get('/programme/creneaux', AdminProgramSlots::class)->name('admin.program.slots');

        Route::get('/attestations', AdminAttestations::class)->name('admin.documents.attestations');
        Route::get('/modeles', AdminTemplateDesigner::class)->name('admin.documents.templates');
        Route::get('/pointage', AdminBadgeScan::class)->name('admin.badges.scan');
        Route::get('/editions/{edition}/livret', [DocumentController::class, 'livret'])->name('admin.documents.livret');
        Route::get('/editions/{edition}/badges', [DocumentController::class, 'badges'])->name('admin.documents.badges');
        Route::get('/editions/{edition}/fiches-session', [DocumentController::class, 'sessionSheets'])->name('admin.documents.sessions');

        Route::get('/reglages', AdminSettings::class)->name('admin.settings');
        Route::get('/reglages/agents-ia', AdminSettingsAgents::class)->name('admin.settings.agents');
        Route::get('/assistant-ia', AdminAiAssistant::class)->name('admin.ai');

        Route::post('/deconnexion', AdminLogoutController::class)->name('admin.logout');
    });
});
