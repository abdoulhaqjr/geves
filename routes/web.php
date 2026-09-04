<?php

use App\Http\Controllers\Account\ColloqueContextController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\LogoutController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\PaymentController as AccountPaymentController;
use App\Http\Controllers\Account\PreferencesController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RegisterController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SubmissionController;
use App\Http\Controllers\Account\VerifyEmailController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\BadgeScanController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentHubController;
use App\Http\Controllers\Admin\EditionController;
use App\Http\Controllers\Admin\EditionFormController;
use App\Http\Controllers\Admin\EditionMediaController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingAgentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubmissionFormController;
use App\Http\Controllers\Admin\TemplateDesignerController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttestationController;
use App\Http\Controllers\EgressController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\CoauthorController;
use App\Http\Controllers\Public\ColloqueController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DeroulementController;
use App\Http\Controllers\Public\GescoController;
use App\Http\Controllers\Reviewer\ReviewerController;
use App\Http\Middleware\StampAccountSession;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Page publique d'une édition de colloque.
Route::get('/deroulement', DeroulementController::class)->name('public.deroulement');
Route::get('/assistante', [GescoController::class, 'page'])->name('public.gesco');
Route::post('/gesco/demander', [GescoController::class, 'ask'])->name('gesco.ask');

Route::get('/colloques-passes', [ArchiveController::class, 'index'])->name('public.archives');
Route::get('/colloques-passes/{slug}', [ArchiveController::class, 'show'])->name('public.archive');

Route::get('/colloque/{slug}', ColloqueController::class)->name('public.colloque');
Route::post('/colloque/{edition}/contact', [ContactController::class, 'send'])->name('public.contact');

// Notifications (participant ou staff — l'un ou l'autre guard).
Route::middleware('auth:account,web')->group(function () {
    Route::post('/notifications/{id}/ouvrir', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/preferences', [NotificationController::class, 'savePrefs'])->name('notifications.prefs');
});

// Parcours de paiement des co-auteurs (public, sans compte, retrouvé par e-mail).
Route::get('/colloque/{slug}/co-auteur', [CoauthorController::class, 'show'])->name('public.coauthor');
Route::post('/colloque/{slug}/co-auteur', [CoauthorController::class, 'pay'])->name('public.coauthor.pay');

// Visio interactive d'une session (accès géré dans le contrôleur selon le rôle).
Route::get('/session/{session}/visio', [MeetingController::class, 'join'])->name('session.meeting');
// Enregistrement (LiveKit Egress) — réservé au modérateur (contrôle dans le contrôleur).
Route::post('/session/{session}/egress/start', [EgressController::class, 'start'])->name('session.egress.start');
Route::post('/session/{session}/egress/stop', [EgressController::class, 'stop'])->name('session.egress.stop');

// Portail relecteur (accès par jeton, sans compte).
Route::get('/relecture/{token}', [ReviewerController::class, 'portal'])->name('reviewer.portal');
Route::get('/relecture/{token}/{review}', [ReviewerController::class, 'evaluate'])->name('reviewer.evaluate');
Route::post('/relecture/{token}/{review}', [ReviewerController::class, 'submitEvaluation'])->name('reviewer.evaluate.submit');

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
    Route::get('/inscription', [RegisterController::class, 'create'])->name('account.register');
    Route::post('/inscription', [RegisterController::class, 'store'])->name('account.register.store');

    Route::get('/connexion', [LoginController::class, 'create'])->name('account.login');
    Route::post('/connexion', [LoginController::class, 'store'])->name('account.login.store');
    Route::post('/connexion/verification', [LoginController::class, 'confirm'])->name('account.login.confirm');
    Route::post('/connexion/verification/renvoyer', [LoginController::class, 'resendOtp'])->name('account.login.resend');
    Route::post('/connexion/retour', [LoginController::class, 'backToLogin'])->name('account.login.back');

    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'request'])->name('account.password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'email'])->name('account.password.email');
    Route::get('/reinitialiser/{token}', [PasswordResetController::class, 'reset'])->name('account.password.reset');
    Route::post('/reinitialiser', [PasswordResetController::class, 'update'])->name('account.password.update');
});

Route::middleware(['auth:account', StampAccountSession::class])->group(function () {
    Route::get('/mon-compte', DashboardController::class)->name('account.dashboard');
    Route::post('/mon-compte/colloque', [ColloqueContextController::class, 'switch'])->name('account.colloque.switch');
    Route::get('/mon-profil', [ProfileController::class, 'edit'])->name('account.profile');
    Route::put('/mon-profil', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::get('/mon-profil/affiliations', [ProfileController::class, 'affiliations'])->name('account.profile.affiliations');
    Route::get('/mes-documents', [App\Http\Controllers\Account\DocumentController::class, 'index'])->name('account.documents');
    Route::get('/mes-notifications', [NotificationController::class, 'index'])->name('account.notifications');
    Route::get('/mon-compte/preferences', [PreferencesController::class, 'edit'])->name('account.preferences');
    Route::put('/mon-compte/preferences', [PreferencesController::class, 'update'])->name('account.preferences.update');
    Route::get('/mon-compte/securite', [SecurityController::class, 'index'])->name('account.security');
    Route::post('/mon-compte/securite/mot-de-passe', [SecurityController::class, 'updatePassword'])->name('account.security.password');
    Route::post('/mon-compte/securite/2fa/totp/demarrer', [SecurityController::class, 'startSetup'])->name('account.security.totp.start');
    Route::post('/mon-compte/securite/2fa/totp/annuler', [SecurityController::class, 'cancelSetup'])->name('account.security.totp.cancel');
    Route::post('/mon-compte/securite/2fa/totp/confirmer', [SecurityController::class, 'confirmTwoFactor'])->name('account.security.totp.confirm');
    Route::post('/mon-compte/securite/2fa/sms/demarrer', [SecurityController::class, 'startSmsSetup'])->name('account.security.sms.start');
    Route::post('/mon-compte/securite/2fa/sms/envoyer', [SecurityController::class, 'sendSmsSetupCode'])->name('account.security.sms.send');
    Route::post('/mon-compte/securite/2fa/sms/confirmer', [SecurityController::class, 'confirmSmsSetup'])->name('account.security.sms.confirm');
    Route::post('/mon-compte/securite/2fa/sms/annuler', [SecurityController::class, 'cancelSmsSetup'])->name('account.security.sms.cancel');
    Route::post('/mon-compte/securite/2fa/codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('account.security.recovery');
    Route::post('/mon-compte/securite/2fa/desactiver', [SecurityController::class, 'disableTwoFactor'])->name('account.security.disable');
    Route::post('/mon-compte/securite/sessions/{id}/revoquer', [SecurityController::class, 'revokeSession'])->name('account.security.sessions.revoke');
    Route::post('/mon-compte/securite/sessions/autres', [SecurityController::class, 'revokeOtherSessions'])->name('account.security.sessions.revoke-others');

    // Communications
    Route::get('/mes-communications', [SubmissionController::class, 'index'])->name('account.submissions.index');
    Route::get('/mes-communications/{submission}', [SubmissionController::class, 'show'])->name('account.submissions.show');
    Route::post('/mes-communications/{submission}/resoumettre', [SubmissionController::class, 'resubmit'])->name('account.submissions.resubmit');
    Route::post('/mes-communications/{submission}/creneau', [SubmissionController::class, 'chooseSlot'])->name('account.submissions.choose-slot');
    Route::get('/colloque/{slug}/soumettre', [SubmissionController::class, 'create'])->name('account.submissions.create');
    Route::post('/colloque/{slug}/soumettre', [SubmissionController::class, 'store'])->name('account.submissions.store');

    // Paiement des frais
    Route::get('/mon-compte/paiement/{edition}', [AccountPaymentController::class, 'show'])->name('account.payment');
    Route::post('/mon-compte/paiement/{edition}', [AccountPaymentController::class, 'pay'])->name('account.payment.pay');
    Route::get('/mon-compte/recu/{payment}', App\Http\Controllers\Account\ReceiptController::class)->name('account.receipt');
    Route::get('/mon-compte/edition/{edition}/livret', [App\Http\Controllers\Account\DocumentController::class, 'livret'])->name('account.documents.livret');

    // Vérification d'e-mail
    Route::get('/verification-email', [VerifyEmailController::class, 'notice'])->name('account.verification.notice');
    Route::post('/verification-email/renvoyer', [VerifyEmailController::class, 'resend'])->name('account.verification.resend');
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
    // Page de connexion unique : /admin/connexion redirige vers /connexion
    // (le repli staff est géré par le contrôleur de connexion participant).
    Route::get('/connexion', [AdminLoginController::class, 'create'])->name('admin.login');

    // Vérification d'e-mail du staff (lien signé, sans session requise).
    Route::get('/verifier-email/{id}/{hash}', App\Http\Controllers\Admin\EmailVerificationController::class)
        ->middleware('signed')->name('admin.verification.verify');

    Route::middleware('auth:web')->group(function () {
        // Accès de base au back-office (tous les rôles UKC ont `view_dashboard`).
        Route::get('/', App\Http\Controllers\Admin\DashboardController::class)->middleware('can:view_dashboard')->name('admin.dashboard');
        Route::get('/notifications', [NotificationController::class, 'index'])->middleware('can:view_dashboard')->name('admin.notifications');
        Route::post('/deconnexion', AdminLogoutController::class)->name('admin.logout');

        // Éditions : lecture / écriture.
        Route::get('/editions', [EditionController::class, 'index'])->middleware('can:editions_read')->name('admin.editions.index');
        Route::get('/editions/{edition}/exporter', [EditionController::class, 'export'])->middleware('can:editions_read')->name('admin.editions.export');
        Route::middleware('can:editions_write')->group(function () {
            Route::post('/editions/{edition}/importer', [EditionController::class, 'import'])->name('admin.editions.import');
            Route::post('/editions/{edition}/activer', [EditionController::class, 'activate'])->name('admin.editions.activate');
            Route::delete('/editions/{edition}', [EditionController::class, 'destroy'])->name('admin.editions.destroy');
            Route::get('/editions/creer', [EditionFormController::class, 'create'])->name('admin.editions.create');
            Route::post('/editions/creer', [EditionFormController::class, 'store'])->name('admin.editions.store');
            Route::post('/editions/ia-assist', [EditionFormController::class, 'aiAssist'])->name('admin.editions.ai-assist');
            Route::post('/editions/ia-assist-json', [EditionFormController::class, 'aiAssistJson'])->name('admin.editions.ai-assist-json');
            Route::get('/editions/{edition}/modifier', [EditionFormController::class, 'edit'])->name('admin.editions.edit');
            Route::put('/editions/{edition}/modifier', [EditionFormController::class, 'update'])->name('admin.editions.update');
            Route::get('/editions/{edition}/formulaire', [SubmissionFormController::class, 'edit'])->name('admin.editions.form');
            Route::put('/editions/{edition}/formulaire', [SubmissionFormController::class, 'update'])->name('admin.editions.form.update');
            Route::get('/editions/{edition}/documents', [EditionMediaController::class, 'index'])->name('admin.editions.documents');
            Route::post('/editions/{edition}/documents', [EditionMediaController::class, 'store'])->name('admin.editions.documents.store');
            Route::delete('/editions/{edition}/documents/{medium}', [EditionMediaController::class, 'destroy'])->name('admin.editions.documents.destroy');
        });

        // Soumissions.
        Route::middleware('can:submissions_list')->group(function () {
            Route::get('/soumissions', [App\Http\Controllers\Admin\SubmissionController::class, 'index'])->name('admin.submissions.index');
            Route::get('/soumissions/export/csv', [App\Http\Controllers\Admin\SubmissionController::class, 'export'])->name('admin.submissions.export');
            Route::get('/soumissions/{submission}', [App\Http\Controllers\Admin\SubmissionController::class, 'show'])->name('admin.submissions.show');
            Route::post('/soumissions/{submission}/statut', [App\Http\Controllers\Admin\SubmissionController::class, 'applyStatus'])->name('admin.submissions.status');
            Route::post('/soumissions/{submission}/relecteur', [App\Http\Controllers\Admin\SubmissionController::class, 'assignReviewer'])->name('admin.submissions.review.assign');
            Route::delete('/soumissions/{submission}/relecteur/{review}', [App\Http\Controllers\Admin\SubmissionController::class, 'removeReview'])->name('admin.submissions.review.remove');
        });

        // Paiements.
        Route::middleware('can:payments')->group(function () {
            Route::get('/paiements', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('admin.payments.index');
            Route::get('/paiements/export/csv', [App\Http\Controllers\Admin\PaymentController::class, 'export'])->name('admin.payments.export');
            Route::post('/paiements/{payment}/regler', [App\Http\Controllers\Admin\PaymentController::class, 'markPaid'])->name('admin.payments.mark-paid');
            Route::post('/paiements/{payment}/rembourser', [App\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('admin.payments.refund');
            Route::get('/paiements/creer', [App\Http\Controllers\Admin\PaymentController::class, 'create'])->name('admin.payments.create');
            Route::post('/paiements/creer', [App\Http\Controllers\Admin\PaymentController::class, 'store'])->name('admin.payments.store');
            Route::get('/paiements/{payment}/recu', ReceiptController::class)->name('admin.payments.receipt');
        });

        // Utilisateurs & rôles.
        Route::middleware('can:users_roles')->group(function () {
            Route::get('/utilisateurs', [UserController::class, 'index'])->name('admin.users.index');
            Route::get('/utilisateurs/creer', [UserController::class, 'create'])->name('admin.users.create');
            Route::post('/utilisateurs', [UserController::class, 'store'])->name('admin.users.store');
            Route::put('/utilisateurs/matrice-acces', [UserController::class, 'updateAccess'])->name('admin.users.access');
            Route::get('/utilisateurs/{user}/modifier', [UserController::class, 'edit'])->name('admin.users.edit');
            Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->name('admin.users.update');
            Route::post('/utilisateurs/{user}/verification', [UserController::class, 'resendVerification'])->name('admin.users.resend');
            Route::delete('/utilisateurs/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        });

        // Participants (l'impersonation est en plus gardée par `manage_colloques`
        // dans le contrôleur).
        Route::middleware('can:participants')->group(function () {
            Route::get('/participants', [ParticipantController::class, 'index'])->name('admin.participants.index');
            Route::post('/participants/inviter-payes', [ParticipantController::class, 'inviteBulk'])->name('admin.participants.invite-bulk');
            Route::post('/participants/{participant}/inviter', [ParticipantController::class, 'invite'])->name('admin.participants.invite');
            Route::get('/participants/{participant}/apercu', [ParticipantController::class, 'preview'])->name('admin.participants.preview');
            Route::post('/participants/{participant}/impersonate', [ImpersonationController::class, 'take'])->name('admin.participants.impersonate');
        });

        // E-mails.
        Route::middleware('can:emails')->group(function () {
            Route::get('/emails', [EmailController::class, 'index'])->name('admin.emails.index');
            Route::post('/emails/envoyer', [EmailController::class, 'send'])->name('admin.emails.send');
            Route::post('/emails/envoyer-dus', [EmailController::class, 'sendDue'])->name('admin.emails.send-due');
            Route::delete('/emails/{email}', [EmailController::class, 'cancel'])->name('admin.emails.cancel');
            Route::post('/emails/annuler-campagne', [EmailController::class, 'cancelBatch'])->name('admin.emails.cancel-batch');
            Route::post('/emails/relancer-campagne', [EmailController::class, 'retryBatch'])->name('admin.emails.retry-batch');
            Route::put('/emails/modeles', [EmailTemplateController::class, 'update'])->name('admin.emails.templates.update');
            Route::post('/emails/modeles/nouveau', [EmailTemplateController::class, 'store'])->name('admin.emails.templates.store');
            Route::delete('/emails/modeles/{key}', [EmailTemplateController::class, 'destroy'])->name('admin.emails.templates.destroy');
        });

        // Rapports (lecture d'ensemble).
        Route::middleware('can:view_dashboard')->group(function () {
            Route::get('/rapports', [ReportController::class, 'index'])->name('admin.reports.index');
            Route::post('/rapports/generer', [ReportController::class, 'generate'])->name('admin.reports.generate');
            Route::get('/rapports/{edition}/excel', [ReportController::class, 'xlsx'])->name('admin.reports.xlsx');
            Route::get('/rapports/{edition}/pdf', [ReportController::class, 'pdf'])->name('admin.reports.pdf');
            Route::get('/rapports/{edition}/export/{dataset}/{format}', [ReportController::class, 'export'])->name('admin.reports.export');
            Route::get('/rapports/{edition}/ia/{id}/telecharger', [ReportController::class, 'aiDownload'])->name('admin.reports.ai-download');
        });

        // Programme / créneaux (structure de l'édition).
        Route::middleware('can:editions_read')->group(function () {
            Route::get('/programme', [ProgramController::class, 'sessions'])->name('admin.program.sessions');
            Route::post('/programme/ateliers', [ProgramController::class, 'storeAtelier'])->name('admin.program.ateliers.store');
            Route::put('/programme/ateliers/{atelier}', [ProgramController::class, 'updateAtelier'])->name('admin.program.ateliers.update');
            Route::post('/programme/ateliers/{atelier}/salle', [ProgramController::class, 'assignAtelierRoom'])->name('admin.program.ateliers.room');
            Route::delete('/programme/ateliers/{atelier}', [ProgramController::class, 'deleteAtelier'])->name('admin.program.ateliers.destroy');
            Route::post('/programme/sessions', [ProgramController::class, 'storeSession'])->name('admin.program.sessions.store');
            Route::put('/programme/sessions/{session}', [ProgramController::class, 'updateSession'])->name('admin.program.sessions.update');
            Route::delete('/programme/sessions/{session}', [ProgramController::class, 'deleteSession'])->name('admin.program.sessions.destroy');
            Route::post('/programme/sessions/{session}/visio/statut', [ProgramController::class, 'setMeetingStatus'])->name('admin.program.sessions.meeting-status');
            Route::post('/programme/sessions/{session}/visio/acces', [ProgramController::class, 'setMeetingVisibility'])->name('admin.program.sessions.meeting-visibility');
            Route::post('/programme/sessions/{session}/visio', [ProgramController::class, 'saveMeeting'])->name('admin.program.sessions.meeting');
            Route::get('/programme/creneaux', [ProgramController::class, 'slots'])->name('admin.program.slots');
            Route::post('/programme/creneaux', [ProgramController::class, 'storeSlot'])->name('admin.program.slots.store');
            Route::delete('/programme/creneaux/{slot}', [ProgramController::class, 'deleteSlot'])->name('admin.program.slots.destroy');
            Route::put('/programme/creneaux/{slot}', [ProgramController::class, 'updateSlot'])->name('admin.program.slots.update');
            Route::post('/programme/creneaux/{slot}/assigner', [ProgramController::class, 'assignSlot'])->name('admin.program.slots.assign');
            Route::post('/programme/creneaux/generer', [ProgramController::class, 'generateSlots'])->name('admin.program.slots.generate');
            Route::post('/programme/affectation-auto', [ProgramController::class, 'autoAssignSlots'])->name('admin.program.slots.auto');
            Route::post('/programme/config', [ProgramController::class, 'saveProgramConfig'])->name('admin.program.config');
            Route::post('/programme/espaces', [ProgramController::class, 'storeSpace'])->name('admin.program.spaces.store');
            Route::delete('/programme/espaces/{index}', [ProgramController::class, 'deleteSpace'])->name('admin.program.spaces.destroy');
        });

        // Documents (attestations, badges, livret, fiches).
        Route::middleware('can:documents')->group(function () {
            Route::get('/documents', [DocumentHubController::class, 'index'])->name('admin.documents.attestations');
            Route::get('/attestations', [DocumentHubController::class, 'issued'])->name('admin.attestations.index');
            Route::post('/attestations/emettre', [DocumentHubController::class, 'issueType'])->name('admin.documents.attestations.issue');
            Route::post('/attestations/{attestation}/revoquer', [DocumentHubController::class, 'revoke'])->name('admin.documents.attestations.revoke');
            Route::post('/attestations/{attestation}/retablir', [DocumentHubController::class, 'restore'])->name('admin.documents.attestations.restore');
            Route::post('/attestations/options', [DocumentHubController::class, 'saveAttestationOptions'])->name('admin.documents.attestations.options');
            Route::post('/badges/options', [DocumentHubController::class, 'saveBadgeOptions'])->name('admin.documents.badges.options');
            Route::post('/livret/options', [DocumentHubController::class, 'saveLivretOptions'])->name('admin.documents.livret.options');
            Route::post('/livret/avant-propos-ia', [DocumentHubController::class, 'generateForewordAi'])->name('admin.documents.livret.foreword-ai');
            Route::post('/livret/apparence', [DocumentHubController::class, 'saveLivretApparence'])->name('admin.documents.livret.apparence');
            Route::post('/livret/modele', [DocumentHubController::class, 'saveLivretTemplate'])->name('admin.documents.livret.template');
            Route::get('/livret/modele-base', [DocumentHubController::class, 'baseTemplate'])->name('admin.documents.livret.base-template');
            Route::get('/modeles', [TemplateDesignerController::class, 'index'])->name('admin.documents.templates');
            Route::post('/modeles/generer', [TemplateDesignerController::class, 'generate'])->name('admin.documents.templates.generate');
            Route::post('/modeles/enregistrer', [TemplateDesignerController::class, 'save'])->name('admin.documents.templates.save');
            Route::get('/editions/{edition}/livret', [DocumentController::class, 'livret'])->name('admin.documents.livret');
            Route::get('/editions/{edition}/badges', [DocumentController::class, 'badges'])->name('admin.documents.badges');
            Route::get('/editions/{edition}/fiches-session', [DocumentController::class, 'sessionSheets'])->name('admin.documents.sessions');
        });

        // Pointage / scan de badges le jour J.
        Route::middleware('can:badge_scan')->group(function () {
            Route::get('/pointage', [BadgeScanController::class, 'page'])->name('admin.badges.scan');
            Route::post('/pointage/rechercher', [BadgeScanController::class, 'lookup'])->name('admin.badges.lookup');
            Route::post('/pointage/{participant}/pointer', [BadgeScanController::class, 'checkIn'])->name('admin.badges.checkin');
        });

        // Réglages (dont agents IA).
        Route::middleware('can:settings')->group(function () {
            Route::get('/reglages', [SettingController::class, 'edit'])->name('admin.settings');
            Route::post('/reglages', [SettingController::class, 'update'])->name('admin.settings.update');
            Route::post('/reglages/test-email', [SettingController::class, 'sendTest'])->name('admin.settings.test');
            Route::get('/reglages/agents-ia', [SettingAgentController::class, 'edit'])->name('admin.settings.agents');
            Route::put('/reglages/agents-ia', [SettingAgentController::class, 'update'])->name('admin.settings.agents.update');
        });

        // Copilote IA (outil d'aide, décision humaine).
        Route::get('/assistant-ia', [AiAssistantController::class, 'page'])->middleware('can:view_dashboard')->name('admin.ai');
        Route::post('/assistant-ia/demander', [AiAssistantController::class, 'ask'])->middleware('can:view_dashboard')->name('admin.ai.ask');
        Route::post('/assistant-ia/decider', [AiAssistantController::class, 'decide'])->middleware('can:view_dashboard')->name('admin.ai.decide');
    });
});
