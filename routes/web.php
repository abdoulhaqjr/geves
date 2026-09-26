<?php

use App\Http\Controllers\Account\AgendaController;
use App\Http\Controllers\Account\AuthorLookupController;
use App\Http\Controllers\Account\ColloqueContextController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\FormationController as AccountFormationController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\LogoutController;
use App\Http\Controllers\Account\ModerationController;
use App\Http\Controllers\Account\OrcidController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\PaymentController as AccountPaymentController;
use App\Http\Controllers\Account\PreferencesController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RegisterController;
use App\Http\Controllers\Account\ReviewWorkspaceController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SessionReportController;
use App\Http\Controllers\Account\SubmissionController;
use App\Http\Controllers\Account\TestimonialController as AccountTestimonialController;
use App\Http\Controllers\Account\VerifyEmailController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentHubController;
use App\Http\Controllers\Admin\EditionController;
use App\Http\Controllers\Admin\EditionFormController;
use App\Http\Controllers\Admin\EditionMediaController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FormationController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SecretariatController;
use App\Http\Controllers\Admin\SettingAgentController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpaceController;
use App\Http\Controllers\Admin\SubmissionFormController;
use App\Http\Controllers\Admin\TemplateDesignerController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttestationController;
use App\Http\Controllers\AuthorLinkController;
use App\Http\Controllers\EgressController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\CoauthorController;
use App\Http\Controllers\Public\ColloqueController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\DeroulementController;
use App\Http\Controllers\Public\GescoController;
use App\Http\Controllers\SessionEngagementController;
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

// Notifications (participant ou staff - l'un ou l'autre guard).
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
// Enregistrement (LiveKit Egress) - réservé au modérateur (contrôle dans le contrôleur).
Route::post('/session/{session}/egress/start', [EgressController::class, 'start'])->name('session.egress.start');
Route::post('/session/{session}/egress/stop', [EgressController::class, 'stop'])->name('session.egress.stop');

// Engagement live d'une séance (Q&A + sondages) - accès géré dans le contrôleur.
Route::get('/session/{session}/echanges', [SessionEngagementController::class, 'page'])->name('session.engagement');
Route::get('/session/{session}/echanges/questions', [SessionEngagementController::class, 'questions'])->name('session.engagement.questions');
Route::post('/session/{session}/echanges/questions', [SessionEngagementController::class, 'ask'])->name('session.engagement.ask');
Route::post('/session/{session}/echanges/questions/{question}/vote', [SessionEngagementController::class, 'vote'])->name('session.engagement.vote');
Route::post('/session/{session}/echanges/questions/{question}/moderation', [SessionEngagementController::class, 'moderate'])->name('session.engagement.moderate');
Route::get('/session/{session}/echanges/sondages', [SessionEngagementController::class, 'polls'])->name('session.engagement.polls');
Route::post('/session/{session}/echanges/sondages', [SessionEngagementController::class, 'createPoll'])->name('session.engagement.polls.create');
Route::post('/session/{session}/echanges/sondages/{poll}/vote', [SessionEngagementController::class, 'votePoll'])->name('session.engagement.polls.vote');
Route::post('/session/{session}/echanges/sondages/{poll}/etat', [SessionEngagementController::class, 'togglePoll'])->name('session.engagement.polls.toggle');

// Changement de langue (i18n) - mémorisé en session (+ préférence si connecté).
Route::get('/langue/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Association auteur ↔ communication : déclinaison publique par jeton.
Route::get('/association/{token}', [AuthorLinkController::class, 'show'])->name('author.decline');
Route::post('/association/{token}', [AuthorLinkController::class, 'decline'])->name('author.decline.confirm');

// Attestations : document imprimable + vérification publique (par UUID).
Route::get('/attestation/{uuid}', [AttestationController::class, 'document'])->name('attestation.document');
Route::get('/attestation/{uuid}/pdf', [AttestationController::class, 'pdf'])->name('attestation.pdf');
Route::get('/attestation/{uuid}/credential.json', [AttestationController::class, 'credential'])->name('attestation.credential');
Route::get('/verifier', [AttestationController::class, 'verifyLanding'])->name('attestation.verify.landing');
Route::get('/verifier/{uuid}', [AttestationController::class, 'verify'])->name('attestation.verify');

// Identité vérifiable de l'émetteur : document DID (did:web) + JWKS. Deux formes
// de résolution did:web (racine et forme par chemin « :colloques »).
Route::get('/.well-known/did.json', [AttestationController::class, 'didDocument'])->name('attestation.did');
Route::get('/colloques/did.json', [AttestationController::class, 'didDocument']);
Route::get('/.well-known/jwks.json', [AttestationController::class, 'jwks'])->name('attestation.jwks');

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
    Route::get('/compte', DashboardController::class)->name('account.dashboard');
    Route::get('/compte/agenda.ics', [AgendaController::class, 'ics'])->name('account.agenda.ics');
    Route::post('/compte/colloque', [ColloqueContextController::class, 'switch'])->name('account.colloque.switch');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('account.profile');
    Route::put('/profil', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::get('/profil/affiliations', [ProfileController::class, 'affiliations'])->name('account.profile.affiliations');
    Route::get('/compte/orcid/connexion', [OrcidController::class, 'connect'])->name('account.orcid.connect');
    Route::get('/compte/orcid/callback', [OrcidController::class, 'callback'])->name('account.orcid.callback');
    Route::get('/documents', [App\Http\Controllers\Account\DocumentController::class, 'index'])->name('account.documents');
    Route::get('/mes-temoignages', [AccountTestimonialController::class, 'index'])->name('account.testimonials');
    Route::post('/mes-temoignages', [AccountTestimonialController::class, 'store'])->name('account.testimonials.store');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('account.notifications');
    Route::get('/compte/preferences', [PreferencesController::class, 'edit'])->name('account.preferences');
    Route::put('/compte/preferences', [PreferencesController::class, 'update'])->name('account.preferences.update');
    Route::get('/compte/securite', [SecurityController::class, 'index'])->name('account.security');
    Route::post('/compte/securite/mot-de-passe', [SecurityController::class, 'updatePassword'])->name('account.security.password');
    Route::post('/compte/securite/2fa/totp/demarrer', [SecurityController::class, 'startSetup'])->name('account.security.totp.start');
    Route::post('/compte/securite/2fa/totp/annuler', [SecurityController::class, 'cancelSetup'])->name('account.security.totp.cancel');
    Route::post('/compte/securite/2fa/totp/confirmer', [SecurityController::class, 'confirmTwoFactor'])->name('account.security.totp.confirm');
    Route::post('/compte/securite/2fa/sms/demarrer', [SecurityController::class, 'startSmsSetup'])->name('account.security.sms.start');
    Route::post('/compte/securite/2fa/sms/envoyer', [SecurityController::class, 'sendSmsSetupCode'])->name('account.security.sms.send');
    Route::post('/compte/securite/2fa/sms/confirmer', [SecurityController::class, 'confirmSmsSetup'])->name('account.security.sms.confirm');
    Route::post('/compte/securite/2fa/sms/annuler', [SecurityController::class, 'cancelSmsSetup'])->name('account.security.sms.cancel');
    Route::post('/compte/securite/2fa/codes', [SecurityController::class, 'regenerateRecoveryCodes'])->name('account.security.recovery');
    Route::post('/compte/securite/2fa/desactiver', [SecurityController::class, 'disableTwoFactor'])->name('account.security.disable');
    Route::post('/compte/securite/sessions/{id}/revoquer', [SecurityController::class, 'revokeSession'])->name('account.security.sessions.revoke');
    Route::post('/compte/securite/sessions/autres', [SecurityController::class, 'revokeOtherSessions'])->name('account.security.sessions.revoke-others');

    // Communications
    Route::post('/communications/verifier-auteur', [AuthorLookupController::class, 'check'])->name('account.authors.check');
    Route::get('/communications/affiliations', [SubmissionController::class, 'affiliations'])->name('account.affiliations');
    Route::get('/communications', [SubmissionController::class, 'index'])->name('account.submissions.index');
    Route::get('/communications/{submission}', [SubmissionController::class, 'show'])->name('account.submissions.show');
    Route::post('/communications/{submission}/modifier', [SubmissionController::class, 'updateContent'])->name('account.submissions.update');
    Route::post('/communications/{submission}/resoumettre', [SubmissionController::class, 'resubmit'])->name('account.submissions.resubmit');
    Route::post('/communications/{submission}/version-definitive', [SubmissionController::class, 'cameraReady'])->name('account.submissions.camera-ready');
    Route::post('/communications/{submission}/creneau', [SubmissionController::class, 'chooseSlot'])->name('account.submissions.choose-slot');
    Route::post('/communications/{submission}/reponse-relecteurs', [SubmissionController::class, 'rebuttal'])->name('account.submissions.rebuttal');
    Route::get('/colloque/{slug}/soumettre', [SubmissionController::class, 'create'])->name('account.submissions.create');
    Route::post('/colloque/{slug}/soumettre', [SubmissionController::class, 'store'])->name('account.submissions.store');

    // Fonctions événementielles (modérateur / rapporteur / relecteur)
    Route::get('/animation', [ModerationController::class, 'index'])->name('account.moderation');
    Route::get('/rapports', [SessionReportController::class, 'index'])->name('account.reports');
    Route::get('/rapports/{session}', [SessionReportController::class, 'edit'])->name('account.reports.edit');
    Route::post('/rapports/{session}', [SessionReportController::class, 'update'])->name('account.reports.update');
    Route::get('/relectures', [ReviewWorkspaceController::class, 'index'])->name('account.reviews');
    Route::post('/relectures/candidatures', [ReviewWorkspaceController::class, 'saveBids'])->name('account.reviews.bids');
    Route::post('/relectures/{review}', [ReviewWorkspaceController::class, 'submit'])->name('account.reviews.submit');

    // Paiement des frais
    Route::get('/compte/paiement/{edition}', [AccountPaymentController::class, 'show'])->name('account.payment');
    Route::post('/compte/paiement/{edition}', [AccountPaymentController::class, 'pay'])->name('account.payment.pay');
    Route::get('/mes-formations', [AccountFormationController::class, 'index'])->name('account.formations');
    Route::post('/mes-formations/{formation}/inscription', [AccountFormationController::class, 'enroll'])->name('account.formations.enroll');
    Route::get('/mes-formations/{formation}/visio', [AccountFormationController::class, 'visio'])->name('account.formations.visio');
    Route::get('/compte/recu/{payment}', App\Http\Controllers\Account\ReceiptController::class)->name('account.receipt');
    Route::get('/compte/edition/{edition}/livret', [App\Http\Controllers\Account\DocumentController::class, 'livret'])->name('account.documents.livret');

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
        // Accès de base au back-office : ouvert à tout membre du staff connecté
        // (le contenu est ensuite restreint aux éditions accessibles).
        Route::get('/', App\Http\Controllers\Admin\DashboardController::class)->name('admin.dashboard');
        Route::get('/secretariat', [SecretariatController::class, 'index'])->name('admin.secretariat');
        Route::get('/reclamations', [ClaimController::class, 'index'])->name('admin.claims.index');
        Route::get('/reclamations/{claim}', [ClaimController::class, 'show'])->name('admin.claims.show');
        Route::post('/reclamations/{claim}/statut', [ClaimController::class, 'updateStatus'])->name('admin.claims.status');
        Route::post('/reclamations/{claim}/assigner', [ClaimController::class, 'assign'])->name('admin.claims.assign');
        Route::post('/reclamations/{claim}/analyser', [ClaimController::class, 'analyze'])->name('admin.claims.analyze');
        Route::post('/secretariat/assistant', [SecretariatController::class, 'generate'])->name('admin.secretariat.assist');
        Route::get('/secretariat/assistant/{edition}/{id}/telecharger', [SecretariatController::class, 'download'])->name('admin.secretariat.assist.download');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('admin.notifications');
        Route::post('/deconnexion', AdminLogoutController::class)->name('admin.logout');

        // Basculer vers son propre espace participant (identité unifiée).
        Route::post('/espace-participant', [SpaceController::class, 'participant'])->name('admin.space.participant');

        // Profil du staff connecté (identité, photo, mot de passe).
        Route::get('/profil', [App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('admin.profile');
        Route::put('/profil', [App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('admin.profile.update');
        Route::put('/profil/mot-de-passe', [App\Http\Controllers\Admin\ProfileController::class, 'updatePassword'])->name('admin.profile.password');
        Route::post('/profil/2fa/activer', [App\Http\Controllers\Admin\ProfileController::class, 'startTwoFactor'])->name('admin.profile.2fa.start');
        Route::post('/profil/2fa/annuler', [App\Http\Controllers\Admin\ProfileController::class, 'cancelTwoFactor'])->name('admin.profile.2fa.cancel');
        Route::post('/profil/2fa/confirmer', [App\Http\Controllers\Admin\ProfileController::class, 'confirmTwoFactor'])->name('admin.profile.2fa.confirm');
        Route::post('/profil/2fa/codes', [App\Http\Controllers\Admin\ProfileController::class, 'regenerateRecovery'])->name('admin.profile.2fa.recovery');
        Route::delete('/profil/2fa', [App\Http\Controllers\Admin\ProfileController::class, 'disableTwoFactor'])->name('admin.profile.2fa.disable');

        // Éditions : lecture / écriture (étanchéité par édition).
        Route::get('/editions', [EditionController::class, 'index'])->middleware('edition.cap:editions_read')->name('admin.editions.index');
        Route::get('/editions/{edition}/exporter', [EditionController::class, 'export'])->middleware('edition.cap:editions_read')->name('admin.editions.export');
        Route::middleware('edition.cap:editions_write')->group(function () {
            Route::post('/editions/{edition}/importer', [EditionController::class, 'import'])->name('admin.editions.import');
            Route::post('/editions/{edition}/activer', [EditionController::class, 'activate'])->name('admin.editions.activate');
            Route::delete('/editions/{edition}', [EditionController::class, 'destroy'])->name('admin.editions.destroy');
            Route::get('/editions/creer', [EditionFormController::class, 'create'])->name('admin.editions.create');
            Route::post('/editions/creer', [EditionFormController::class, 'store'])->name('admin.editions.store');
            Route::post('/editions/ia-assist', [EditionFormController::class, 'aiAssist'])->name('admin.editions.ai-assist');
            Route::post('/editions/ia-assist-json', [EditionFormController::class, 'aiAssistJson'])->name('admin.editions.ai-assist-json');
            Route::get('/editions/{edition}/modifier', [EditionFormController::class, 'edit'])->name('admin.editions.edit');
            Route::put('/editions/{edition}/modifier', [EditionFormController::class, 'update'])->name('admin.editions.update');
            Route::post('/editions/{edition}/relecteurs/expertise', [EditionFormController::class, 'saveReviewerExpertise'])->name('admin.editions.reviewers.expertise');
            Route::post('/editions/{edition}/relecteurs/grille', [EditionFormController::class, 'saveReviewGrid'])->name('admin.editions.reviewers.criteria');
            Route::post('/editions/{edition}/paiement', [EditionFormController::class, 'savePayment'])->name('admin.editions.payment');
            Route::post('/editions/{edition}/traductions', [EditionFormController::class, 'saveTranslations'])->name('admin.editions.translations');
            Route::post('/editions/{edition}/equipe', [EditionFormController::class, 'attachTeamMember'])->name('admin.editions.team.attach');
            Route::delete('/editions/{edition}/equipe', [EditionFormController::class, 'detachTeamMember'])->name('admin.editions.team.detach');
            Route::get('/editions/{edition}/formulaire', [SubmissionFormController::class, 'edit'])->name('admin.editions.form');
            Route::put('/editions/{edition}/formulaire', [SubmissionFormController::class, 'update'])->name('admin.editions.form.update');
            Route::get('/editions/{edition}/documents', [EditionMediaController::class, 'index'])->name('admin.editions.documents');
            Route::post('/editions/{edition}/documents', [EditionMediaController::class, 'store'])->name('admin.editions.documents.store');
            Route::delete('/editions/{edition}/documents/{medium}', [EditionMediaController::class, 'destroy'])->name('admin.editions.documents.destroy');
        });

        // Soumissions.
        Route::middleware('edition.cap:submissions_list')->group(function () {
            Route::get('/soumissions', [App\Http\Controllers\Admin\SubmissionController::class, 'index'])->name('admin.submissions.index');
            Route::get('/soumissions/export/csv', [App\Http\Controllers\Admin\SubmissionController::class, 'export'])->name('admin.submissions.export');
            Route::get('/soumissions/export/actes/{format}', [App\Http\Controllers\Admin\SubmissionController::class, 'bibliography'])->name('admin.submissions.bibliography');
            // Attribution automatique des relecteurs (route statique AVANT le joker {submission}).
            Route::get('/soumissions/attribution-auto', [App\Http\Controllers\Admin\SubmissionController::class, 'autoAssign'])->name('admin.submissions.auto-assign');
            Route::post('/soumissions/attribution-auto', [App\Http\Controllers\Admin\SubmissionController::class, 'autoAssignApply'])->name('admin.submissions.auto-assign.apply');
            Route::get('/soumissions/{submission}', [App\Http\Controllers\Admin\SubmissionController::class, 'show'])->name('admin.submissions.show');
            Route::post('/soumissions/{submission}/statut', [App\Http\Controllers\Admin\SubmissionController::class, 'applyStatus'])->name('admin.submissions.status');
            Route::post('/soumissions/{submission}/reponse-relecteurs', [App\Http\Controllers\Admin\SubmissionController::class, 'toggleRebuttal'])->name('admin.submissions.rebuttal.toggle');
            Route::post('/soumissions/{submission}/relecteur', [App\Http\Controllers\Admin\SubmissionController::class, 'assignReviewer'])->name('admin.submissions.review.assign');
            Route::delete('/soumissions/{submission}/relecteur/{review}', [App\Http\Controllers\Admin\SubmissionController::class, 'removeReview'])->name('admin.submissions.review.remove');
            Route::post('/soumissions/{submission}/plagiat', [App\Http\Controllers\Admin\SubmissionController::class, 'checkPlagiarism'])->name('admin.submissions.plagiarism');
            // Rattachement manuel d'un auteur à un compte (correction d'un rapprochement).
            Route::post('/soumissions/{submission}/auteurs/{link}/rattacher', [App\Http\Controllers\Admin\SubmissionController::class, 'linkAuthor'])->name('admin.submissions.author.link');
            Route::post('/soumissions/{submission}/auteurs/{link}/detacher', [App\Http\Controllers\Admin\SubmissionController::class, 'detachAuthor'])->name('admin.submissions.author.detach');
        });

        // Paiements.
        Route::middleware('edition.cap:payments')->group(function () {
            Route::get('/paiements', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('admin.payments.index');
            Route::get('/paiements/export/csv', [App\Http\Controllers\Admin\PaymentController::class, 'export'])->name('admin.payments.export');
            Route::post('/paiements/relance/{participant}', [App\Http\Controllers\Admin\PaymentController::class, 'remindPayment'])->name('admin.payments.remind');
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
        Route::middleware('edition.cap:participants')->group(function () {
            Route::get('/participants', [ParticipantController::class, 'index'])->name('admin.participants.index');
            Route::post('/participants/inviter-payes', [ParticipantController::class, 'inviteBulk'])->name('admin.participants.invite-bulk');
            Route::post('/participants/{participant}/inviter', [ParticipantController::class, 'invite'])->name('admin.participants.invite');
            Route::get('/participants/{participant}/apercu', [ParticipantController::class, 'preview'])->name('admin.participants.preview');
            Route::post('/participants/{participant}/impersonate', [ImpersonationController::class, 'take'])->name('admin.participants.impersonate');
        });

        // E-mails.
        Route::middleware('edition.cap:emails')->group(function () {
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

        // Rapports (par édition).
        Route::middleware('edition.cap:view_dashboard')->group(function () {
            Route::get('/rapports', [ReportController::class, 'index'])->name('admin.reports.index');
            Route::post('/rapports/generer', [ReportController::class, 'generate'])->name('admin.reports.generate');
            Route::get('/rapports/{edition}/excel', [ReportController::class, 'xlsx'])->name('admin.reports.xlsx');
            Route::get('/rapports/{edition}/pdf', [ReportController::class, 'pdf'])->name('admin.reports.pdf');
            Route::get('/rapports/{edition}/export/{dataset}/{format}', [ReportController::class, 'export'])->name('admin.reports.export');
            Route::get('/rapports/{edition}/ia/{id}/telecharger', [ReportController::class, 'aiDownload'])->name('admin.reports.ai-download');
        });

        // Gestion de projet de l'édition (l'édition pilotée comme un projet) -
        // capacité dédiée « project ».
        Route::middleware('edition.cap:project')->group(function () {
            Route::get('/projet', [ProjectController::class, 'index'])->name('admin.project.index');
            Route::get('/projet/export', [ProjectController::class, 'exportXlsx'])->name('admin.project.export');
            Route::get('/projet/rapport', [ProjectController::class, 'report'])->name('admin.project.report');
            Route::post('/projet/initialiser', [ProjectController::class, 'seedTemplate'])->name('admin.project.seed');
            Route::post('/projet/taches', [ProjectController::class, 'storeTask'])->name('admin.project.tasks.store');
            Route::put('/projet/taches/{task}', [ProjectController::class, 'updateTask'])->name('admin.project.tasks.update');
            Route::post('/projet/taches/{task}/deplacer', [ProjectController::class, 'moveTask'])->name('admin.project.tasks.move');
            Route::post('/projet/taches/{task}/echeance', [ProjectController::class, 'rescheduleTask'])->name('admin.project.tasks.reschedule');
            Route::get('/projet/taches/{task}/commentaires', [ProjectController::class, 'comments'])->name('admin.project.tasks.comments');
            Route::post('/projet/taches/{task}/commentaires', [ProjectController::class, 'addComment'])->name('admin.project.tasks.comment');
            Route::delete('/projet/taches/{task}', [ProjectController::class, 'destroyTask'])->name('admin.project.tasks.destroy');
            Route::post('/projet/jalons', [ProjectController::class, 'storeMilestone'])->name('admin.project.milestones.store');
            Route::put('/projet/jalons/{milestone}', [ProjectController::class, 'updateMilestone'])->name('admin.project.milestones.update');
            Route::post('/projet/jalons/{milestone}/basculer', [ProjectController::class, 'toggleMilestone'])->name('admin.project.milestones.toggle');
            Route::post('/projet/jalons/{milestone}/echeance', [ProjectController::class, 'rescheduleMilestone'])->name('admin.project.milestones.reschedule');
            Route::delete('/projet/jalons/{milestone}', [ProjectController::class, 'destroyMilestone'])->name('admin.project.milestones.destroy');
        });

        // Programme / créneaux (structure de l'édition).
        Route::middleware('edition.cap:editions_read')->group(function () {
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
            Route::get('/programme/rapports', [ProgramController::class, 'reports'])->name('admin.program.reports');
            Route::post('/programme/rapports/{session}', [ProgramController::class, 'saveReport'])->name('admin.program.reports.save');
            Route::post('/programme/rapports/{session}/rouvrir', [ProgramController::class, 'reopenReport'])->name('admin.program.reports.reopen');
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

        // Annonces du déroulement (admin, gestionnaire, secrétariat).
        Route::middleware('edition.cap:announcements')->group(function () {
            Route::get('/annonces', [AnnouncementController::class, 'index'])->name('admin.announcements');
            Route::post('/editions/{edition}/annonces', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
            Route::delete('/editions/{edition}/annonces/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
        });

        // Formations (module activable par édition).
        Route::middleware('edition.cap:formation')->group(function () {
            Route::get('/formations', [FormationController::class, 'index'])->name('admin.formations');
            Route::post('/editions/{edition}/formations', [FormationController::class, 'store'])->name('admin.formations.store');
            Route::post('/formations/ia', [FormationController::class, 'aiAssist'])->name('admin.formations.ai');
            Route::put('/formations/{formation}', [FormationController::class, 'update'])->name('admin.formations.update');
            Route::delete('/formations/{formation}', [FormationController::class, 'destroy'])->name('admin.formations.destroy');
            Route::post('/formations/{formation}/formateurs', [FormationController::class, 'storeTrainer'])->name('admin.formations.trainers.store');
            Route::put('/formations/{formation}/formateurs/{trainer}', [FormationController::class, 'updateTrainer'])->name('admin.formations.trainers.update');
            Route::post('/formations/{formation}/formateurs/{trainer}/photo', [FormationController::class, 'updateTrainerPhoto'])->name('admin.formations.trainers.photo');
            Route::delete('/formations/{formation}/formateurs/{trainer}', [FormationController::class, 'destroyTrainer'])->name('admin.formations.trainers.destroy');
            Route::post('/formations/{formation}/message', [FormationController::class, 'message'])->name('admin.formations.message');
            Route::post('/formations/{formation}/attestations', [FormationController::class, 'issueAttestations'])->name('admin.formations.attestations');
            Route::post('/formations/{formation}/visio', [FormationController::class, 'setMeeting'])->name('admin.formations.meeting');
        });

        // Documents (attestations, badges, livret, fiches).
        Route::middleware('edition.cap:documents')->group(function () {
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
            Route::get('/editions/{edition}/pv-seances', [DocumentController::class, 'sessionPv'])->name('admin.documents.session-pv');
            Route::get('/editions/{edition}/mise-en-page-seances', [DocumentController::class, 'sessionLayout'])->name('admin.documents.session-layout');
            Route::put('/editions/{edition}/mise-en-page-seances', [DocumentController::class, 'saveSessionLayout'])->name('admin.documents.session-layout.save');
        });

        // Réglages (dont agents IA).
        // Témoignages (niveau plateforme) - modération, mise en avant, sélection IA.
        Route::middleware('can:testimonials')->group(function () {
            Route::get('/temoignages', [AdminTestimonialController::class, 'index'])->name('admin.testimonials.index');
            Route::post('/temoignages', [AdminTestimonialController::class, 'store'])->name('admin.testimonials.store');
            Route::post('/temoignages/proposer', [AdminTestimonialController::class, 'propose'])->name('admin.testimonials.propose');
            Route::post('/temoignages/ordre', [AdminTestimonialController::class, 'reorder'])->name('admin.testimonials.reorder');
            Route::post('/temoignages/{testimonial}/approuver', [AdminTestimonialController::class, 'approve'])->name('admin.testimonials.approve');
            Route::post('/temoignages/{testimonial}/refuser', [AdminTestimonialController::class, 'reject'])->name('admin.testimonials.reject');
            Route::post('/temoignages/{testimonial}/mise-en-avant', [AdminTestimonialController::class, 'toggleFeatured'])->name('admin.testimonials.feature');
            Route::delete('/temoignages/{testimonial}', [AdminTestimonialController::class, 'destroy'])->name('admin.testimonials.destroy');
        });

        Route::middleware('can:settings')->group(function () {
            Route::get('/reglages', [SettingController::class, 'edit'])->name('admin.settings');
            Route::post('/reglages', [SettingController::class, 'update'])->name('admin.settings.update');
            Route::post('/reglages/test-email', [SettingController::class, 'sendTest'])->name('admin.settings.test');
            Route::get('/reglages/agents-ia', [SettingAgentController::class, 'edit'])->name('admin.settings.agents');
            Route::put('/reglages/agents-ia', [SettingAgentController::class, 'update'])->name('admin.settings.agents.update');
        });

        // Copilote IA (outil d'aide, décision humaine).
        Route::get('/assistant-ia', [AiAssistantController::class, 'page'])->middleware('edition.cap:view_dashboard')->name('admin.ai');
        Route::post('/assistant-ia/demander', [AiAssistantController::class, 'ask'])->middleware('edition.cap:view_dashboard')->name('admin.ai.ask');
        Route::post('/assistant-ia/decider', [AiAssistantController::class, 'decide'])->middleware('edition.cap:view_dashboard')->name('admin.ai.decide');
        Route::post('/assistant-ia/executer', [AiAssistantController::class, 'execute'])->middleware('edition.cap:view_dashboard')->name('admin.ai.execute');
    });
});
