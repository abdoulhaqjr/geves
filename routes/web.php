<?php

use App\Http\Controllers\Account\AgendaController;
use App\Http\Controllers\Account\AuthorLookupController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\EventContextController;
use App\Http\Controllers\Account\FeeCategoryController;
use App\Http\Controllers\Account\FormationController as AccountFormationController;
use App\Http\Controllers\Account\LoginController;
use App\Http\Controllers\Account\LogoutController;
use App\Http\Controllers\Account\ModerationController;
use App\Http\Controllers\Account\OnboardingController as AccountOnboardingController;
use App\Http\Controllers\Account\OrcidController;
use App\Http\Controllers\Account\ParticipationModeController;
use App\Http\Controllers\Account\PasswordResetController;
use App\Http\Controllers\Account\PaymentController as AccountPaymentController;
use App\Http\Controllers\Account\PreferencesController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\RegisterController;
use App\Http\Controllers\Account\ReviewerApplicationController;
use App\Http\Controllers\Account\ReviewWorkspaceController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\SessionAttendanceController;
use App\Http\Controllers\Account\SessionPassageController;
use App\Http\Controllers\Account\SessionReportController;
use App\Http\Controllers\Account\SubmissionController;
use App\Http\Controllers\Account\TestimonialController as AccountTestimonialController;
use App\Http\Controllers\Account\VerifyEmailController;
use App\Http\Controllers\Admin\AiAssistantController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AttestationExportController;
use App\Http\Controllers\Admin\ClaimController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\DocumentHubController;
use App\Http\Controllers\Admin\EmailController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventFormController;
use App\Http\Controllers\Admin\EventMediaController;
use App\Http\Controllers\Admin\FormationController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Http\Controllers\Admin\OnboardingController;
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
use App\Http\Controllers\Admin\TaskDocumentController;
use App\Http\Controllers\Admin\TemplateDesignerController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AttestationController;
use App\Http\Controllers\AuthorLinkController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\EgressController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Internal\TutorialCaptureController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MeetingChatController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\ArchiveController;
use App\Http\Controllers\Public\CoauthorController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CookieController;
use App\Http\Controllers\Public\DeroulementController;
use App\Http\Controllers\Public\EventSeriesController;
use App\Http\Controllers\Public\GescoController;
use App\Http\Controllers\SessionEngagementController;
use App\Http\Controllers\SessionPresenceApiController;
use App\Http\Middleware\StampAccountSession;
use App\Http\Middleware\SyncStaffParticipantSession;
use App\Models\Event;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/csp-report', CspReportController::class)->middleware('throttle:20,1')->name('csp.report');

// Page publique d'un événement de colloque.
Route::get('/deroulement', DeroulementController::class)->name('public.deroulement');
Route::get('/assistante', [GescoController::class, 'page'])->name('public.gesco');

// Inventaire des cookies déposés. C'est la page que vise le bandeau d'information,
// et elle est atteignable depuis le pied de page - sans quoi elle n'existerait que
// pour qui a cliqué sur le bandeau, donc une fois.
Route::get('/cookies', CookieController::class)->name('public.cookies');
Route::post('/gesco/demander', [GescoController::class, 'ask'])->name('gesco.ask');

// Pont interne signé : uniquement le navigateur isolé de production des tutoriels
// l'utilise pour ouvrir de vraies pages GEVES avec le compte de démonstration.
Route::get('/_captures-tutoriels/{guide}/{step}', TutorialCaptureController::class)
    ->middleware('signed')->name('tutorial.capture');

Route::get('/colloques-passes', [ArchiveController::class, 'index'])->name('public.archives');
Route::get('/colloques-passes/{slug}', [ArchiveController::class, 'show'])->name('public.archive');

Route::get('/colloque/{slug}', EventSeriesController::class)->name('public.colloque');
Route::post('/colloque/{event}/contact', [ContactController::class, 'send'])->name('public.contact');

// Notifications (participant ou staff - l'un ou l'autre guard).
Route::middleware('auth:account,web')->group(function () {
    Route::get('/notifications/apercu', [NotificationController::class, 'preview'])->name('notifications.preview');
    Route::post('/notifications/{id}/ouvrir', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/tout-lire', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/preferences', [NotificationController::class, 'savePrefs'])->name('notifications.prefs');
});

// Parcours de paiement des co-auteurs (public, sans compte, retrouvé par e-mail).
// Porte d'entrée des co-auteurs : elle ORIENTE vers l'espace participant et
// n'encaisse plus (cf. CoauthorController). L'URL est conservée parce que les
// e-mails d'acceptation déjà partis la portent.
Route::get('/colloque/{slug}/co-auteur', [CoauthorController::class, 'show'])->name('public.coauthor');

// Visio interactive d'une session (accès géré dans le contrôleur selon le rôle).
Route::get('/session/{session}/visio', [MeetingController::class, 'join'])->name('session.meeting');
// Enregistrement (LiveKit Egress) - réservé au modérateur (contrôle dans le contrôleur).
Route::post('/session/{session}/egress/start', [EgressController::class, 'start'])->name('session.egress.start');
Route::post('/session/{session}/egress/stop', [EgressController::class, 'stop'])->name('session.egress.stop');

// Présence tenue DEPUIS la salle de visio, sans quitter la séance (modérateur ou
// rapporteur ; mêmes règles que la page /compte/seances/{id}/presence).
Route::get('/session/{session}/presence.json', [SessionPresenceApiController::class, 'index'])->name('session.presence.list');
Route::post('/session/{session}/presence', [SessionPresenceApiController::class, 'store'])->name('session.presence.mark');

// Passage d'une communication constaté par qui tient la séance. ⚠️ C'est ce
// geste qui ouvre les attestations de ses auteurs : une seule porte serveur
// pour la salle de visio ET la page de modération.
Route::post('/session/{session}/passage/{slot}', [SessionPassageController::class, 'store'])->name('session.passage');

// Fil de discussion de la salle, commun à tout objet `Meetable` (séance du
// programme OU formation). ⚠️ Le chat ne vivait que sur le canal de données :
// qui arrivait en cours de séance trouvait une salle muette.
Route::get('/visio/{type}/{id}/messages', [MeetingChatController::class, 'index'])->name('meeting.chat.list');
// ⚠️ Débit limité PAR PERSONNE et non par IP (cf. AppServiceProvider) : une
// cérémonie publique se suit depuis le campus, donc depuis quelques adresses
// partagées - compter par IP ferait taire toute une salle.
Route::post('/visio/{type}/{id}/messages', [MeetingChatController::class, 'store'])
    ->middleware('throttle:visio-chat')->name('meeting.chat.post');
Route::post('/visio/{type}/{id}/messages/{uid}/reaction', [MeetingChatController::class, 'react'])
    ->middleware('throttle:visio-reaction')->name('meeting.chat.react');

// Modération et compte rendu : retirer un message du fil, ou l'emporter en
// texte pour le PV. Réservés au modérateur de la séance.
Route::delete('/visio/{type}/{id}/messages/{uid}', [MeetingChatController::class, 'destroy'])->name('meeting.chat.delete');
Route::get('/visio/{type}/{id}/messages.txt', [MeetingChatController::class, 'export'])->name('meeting.chat.export');

// Nouveau jeton d'accès : la salle en redemande un pour se reconnecter après
// une coupure, sans recharger la page. Les droits sont recalculés.
Route::get('/visio/{type}/{id}/jeton', [MeetingController::class, 'token'])->name('meeting.token');

// Engagement live d'une séance (Q&A + sondages) = points d'entrée JSON SEULEMENT,
// consommés par l'onglet « Échanges » de la salle de visio. Plus de page autonome :
// les échanges se tiennent exclusivement dans la visio. Accès géré dans le contrôleur.
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

// Attestations : la page du document EST la page de vérification (le verdict
// s'y écrit via « verification.json »). « /verifier » n'est qu'une saisie de code.
Route::get('/attestation/{uuid}', [AttestationController::class, 'document'])->name('attestation.document');
Route::get('/attestation/{uuid}/pdf', [AttestationController::class, 'pdf'])->name('attestation.pdf');
Route::get('/attestation/{uuid}/credential.json', [AttestationController::class, 'credential'])->name('attestation.credential');
Route::get('/attestation/{uuid}/verification.json', [AttestationController::class, 'verifyJson'])->name('attestation.verify.json');
Route::get('/verifier', [AttestationController::class, 'verifyLanding'])->name('attestation.verify.landing');

// Identité vérifiable de l'émetteur : document DID (did:web) + JWKS. Deux formes
// de résolution did:web (racine et forme par chemin « :colloques »).
Route::get('/.well-known/did.json', [AttestationController::class, 'didDocument'])->name('attestation.did');
Route::get('/colloques/did.json', [AttestationController::class, 'didDocument']);
Route::get('/.well-known/jwks.json', [AttestationController::class, 'jwks'])->name('attestation.jwks');

// Paiement en ligne : retour navigateur + webhook serveur-à-serveur.
Route::get('/paiement/retour/{gateway}', [PaymentController::class, 'return'])->name('payment.return');
// Fin du formulaire embarqué. ⚠️ Bridée : elle appelle le prestataire à
// chaque requête, et n'importe qui peut la viser (comme le retour navigateur).
Route::post('/paiement/verifier/{gateway}', [PaymentController::class, 'verify'])
    ->middleware('throttle:paiement-verif')->name('payment.verify');
Route::post('/webhooks/paiement/{gateway}', [PaymentController::class, 'webhook'])->name('payment.webhook');
// Variante dédiée à un événement : à déclarer chez le marchand quand l'événement
// utilise son PROPRE compte passerelle (évite d'essayer toutes les signatures).
Route::post('/webhooks/paiement/{gateway}/{event}', [PaymentController::class, 'webhook'])->name('payment.webhook.edition');

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

// Anciennes URL de l'espace participant (désormais sous /compte) : des liens de
// notifications déjà enregistrés en base pointent encore ici.
Route::permanentRedirect('/profil', '/compte/profil');
Route::permanentRedirect('/notifications', '/compte/notifications');

Route::middleware(['auth:account,web', SyncStaffParticipantSession::class, StampAccountSession::class])->group(function () {
    Route::get('/compte', DashboardController::class)->name('account.dashboard');
    Route::post('/compte/evaluateur/candidature/{event}', [ReviewerApplicationController::class, 'store'])->name('account.reviewer-applications.store');
    Route::get('/prise-en-main', AccountOnboardingController::class)->name('account.onboarding');
    Route::get('/compte/agenda.ics', [AgendaController::class, 'ics'])->name('account.agenda.ics');
    Route::post('/compte/colloque', [EventContextController::class, 'switch'])->name('account.colloque.switch');
    Route::get('/compte/profil', [ProfileController::class, 'edit'])->name('account.profile');
    Route::put('/compte/profil', [ProfileController::class, 'update'])->name('account.profile.update');
    Route::put('/compte/participation/{event}/mode', ParticipationModeController::class)->name('account.participation.mode.update');
    // Catégorie tarifaire hors grade (ONG, entreprise…), déclarée par le participant.
    Route::put('/compte/participation/{event}/categorie', FeeCategoryController::class)->name('account.participation.fee-category.update');
    Route::get('/compte/profil/affiliations', [ProfileController::class, 'affiliations'])->name('account.profile.affiliations');
    Route::get('/compte/orcid/connexion', [OrcidController::class, 'connect'])->name('account.orcid.connect');
    Route::get('/compte/orcid/callback', [OrcidController::class, 'callback'])->name('account.orcid.callback');
    Route::get('/documents', [App\Http\Controllers\Account\DocumentController::class, 'index'])->name('account.documents');
    Route::put('/documents/badges/{event}/{type}/photo', [App\Http\Controllers\Account\DocumentController::class, 'updateBadgePhoto'])->name('account.documents.badge.photo');
    Route::get('/mes-temoignages', [AccountTestimonialController::class, 'index'])->name('account.testimonials');
    Route::post('/mes-temoignages', [AccountTestimonialController::class, 'store'])->name('account.testimonials.store');
    Route::get('/compte/notifications', [NotificationController::class, 'index'])->name('account.notifications');
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
    Route::put('/communications/{submission}/mode-presentation', [SubmissionController::class, 'updatePresentationMode'])->name('account.submissions.presentation-mode.update');
    // ⚠️ La correction a sa PAGE (`revise`), et le POST qui l'applique reste
    // `resubmit` : la page affiche le formulaire, la resoumission l'enregistre.
    Route::get('/communications/{submission}/corriger', [SubmissionController::class, 'revise'])->name('account.submissions.revise');
    Route::post('/communications/{submission}/resoumettre', [SubmissionController::class, 'resubmit'])->name('account.submissions.resubmit');
    Route::post('/communications/{submission}/creneau', [SubmissionController::class, 'chooseSlot'])->name('account.submissions.choose-slot');
    Route::post('/communications/{submission}/reponse-relecteurs', [SubmissionController::class, 'rebuttal'])->name('account.submissions.rebuttal');
    Route::get('/colloque/{slug}/soumettre', [SubmissionController::class, 'create'])->name('account.submissions.create');
    Route::post('/colloque/{slug}/soumettre', [SubmissionController::class, 'store'])->name('account.submissions.store');

    // Fonctions événementielles (modérateur / rapporteur / relecteur)
    // Un seul mot pour une seule chose : le rôle s'appelle « modérateur »
    // partout ailleurs (jeton de salle, pastille, `moderator_email`).
    Route::get('/moderation', [ModerationController::class, 'index'])->name('account.moderation');
    // Ancienne adresse : des signets et des courriels la portent encore.
    Route::redirect('/animation', '/moderation');
    Route::get('/rapports', [SessionReportController::class, 'index'])->name('account.reports');
    Route::get('/rapports/{session}', [SessionReportController::class, 'edit'])->name('account.reports.edit');
    Route::post('/rapports/{session}', [SessionReportController::class, 'update'])->name('account.reports.update');
    // Présence d'une séance : modérateur OU rapporteur (c'est l'un ou l'autre qui
    // a la salle sous les yeux) - d'où une adresse qui ne dit ni l'un ni l'autre.
    Route::get('/seances/{session}/presence', [SessionAttendanceController::class, 'edit'])->name('account.attendance.edit');
    Route::post('/seances/{session}/presence', [SessionAttendanceController::class, 'update'])->name('account.attendance.update');
    // Validation du passage des communications, hors visio (page dédiée, comme la
    // présence) : modérateur OU rapporteur. Le constat lui-même passe par la route
    // partagée session.passage (même garde horaire).
    Route::get('/seances/{session}/passage', [SessionPassageController::class, 'edit'])->name('account.passage.edit');
    Route::get('/relectures', [ReviewWorkspaceController::class, 'index'])->name('account.reviews');
    Route::post('/relectures/candidatures', [ReviewWorkspaceController::class, 'saveBids'])->name('account.reviews.bids');
    Route::post('/relectures/{review}/reponse', [ReviewWorkspaceController::class, 'respond'])->name('account.reviews.respond');
    Route::post('/relectures/{review}', [ReviewWorkspaceController::class, 'submit'])->name('account.reviews.submit');

    // Paiement des frais
    Route::get('/compte/paiement/{event}', [AccountPaymentController::class, 'show'])->name('account.payment');
    // ⚠️ L'inscription vit SUR la page « Inscription & paiement » : un second
    // formulaire ailleurs ferait un second chemin de création de participant -
    // c'est ce qui avait produit deux fiches pour une même personne sur le
    // parcours co-auteur.
    Route::post('/compte/paiement/{event}/inscription', [AccountPaymentController::class, 'register'])->name('account.registration.store');
    Route::post('/compte/paiement/{event}', [AccountPaymentController::class, 'pay'])->name('account.payment.pay');
    Route::get('/formations', [AccountFormationController::class, 'index'])->name('account.formations');
    Route::post('/formations/{formation}/inscription', [AccountFormationController::class, 'enroll'])->name('account.formations.enroll');
    // Visio d'une formation : page du module visio (MeetingController), la même
    // que pour les sessions du programme (lecteur inline, rôles, arrière-plans).
    Route::get('/formations/{formation}/visio', [MeetingController::class, 'joinFormation'])->name('account.formations.visio');
    Route::get('/compte/recu/{payment}', App\Http\Controllers\Account\ReceiptController::class)->name('account.receipt');
    Route::get('/compte/edition/{event}/livret', [App\Http\Controllers\Account\DocumentController::class, 'livret'])->name('account.documents.livret');

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
        // (le contenu est ensuite restreint aux événements accessibles).
        Route::get('/', App\Http\Controllers\Admin\DashboardController::class)->name('admin.dashboard');
        Route::get('/prise-en-main', OnboardingController::class)->name('admin.onboarding');
        Route::put('/prise-en-main/guides/{guide}', [OnboardingController::class, 'update'])->name('admin.onboarding.guides.update');
        Route::post('/prise-en-main/guides/{guide}/videos/demander', [OnboardingController::class, 'requestVideo'])->name('admin.onboarding.videos.request');
        Route::post('/prise-en-main/videos/demander', [OnboardingController::class, 'requestAllVideos'])->name('admin.onboarding.videos.request-all');
        Route::get('/prise-en-main/videos/{videoRequest}/statut', [OnboardingController::class, 'videoStatus'])->name('admin.onboarding.videos.status');
        Route::get('/secretariat', [SecretariatController::class, 'index'])->name('admin.secretariat');
        // Rapports de séance : lecture, amélioration et validation - métier du secrétariat.
        Route::get('/secretariat/rapports', [SecretariatController::class, 'reports'])->name('admin.secretariat.reports');
        Route::post('/secretariat/rapports/{session}/relancer', [SecretariatController::class, 'remind'])->name('admin.secretariat.remind');
        // Onglet Documents : formulaires + impression des documents logistiques.
        Route::get('/secretariat/documents', [SecretariatController::class, 'documents'])->name('admin.secretariat.documents');
        Route::post('/secretariat/documents/{event}/entete', [SecretariatController::class, 'saveDocHeader'])->name('admin.secretariat.documents.header');
        Route::get('/secretariat/documents/{event}/{type}/imprimer', [SecretariatController::class, 'printDocument'])->name('admin.secretariat.documents.print');
        // Onglet Secrétariat assisté (IA) : écran de génération.
        Route::get('/secretariat/assistant', [SecretariatController::class, 'assistIndex'])->name('admin.secretariat.assistant');
        Route::get('/reclamations', [ClaimController::class, 'index'])->name('admin.claims.index');
        Route::get('/reclamations/{claim}', [ClaimController::class, 'show'])->name('admin.claims.show');
        Route::post('/reclamations/{claim}/statut', [ClaimController::class, 'updateStatus'])->name('admin.claims.status');
        Route::post('/reclamations/{claim}/assigner', [ClaimController::class, 'assign'])->name('admin.claims.assign');
        Route::post('/reclamations/{claim}/analyser', [ClaimController::class, 'analyze'])->name('admin.claims.analyze');
        Route::post('/reclamations/{claim}/transferer', [ClaimController::class, 'transfer'])->name('admin.claims.transfer');
        Route::post('/secretariat/assistant', [SecretariatController::class, 'generate'])->name('admin.secretariat.assist');
        Route::get('/secretariat/assistant/{event}/{id}/telecharger', [SecretariatController::class, 'download'])->name('admin.secretariat.assist.download');
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

        // Événements : lecture / écriture (étanchéité par événement).
        Route::get('/editions', [EventController::class, 'index'])->middleware('edition.cap:editions_read')->name('admin.evenements.index');
        Route::get('/editions/{event}/exporter', [EventController::class, 'export'])->middleware('edition.cap:editions_read')->name('admin.evenements.export');
        Route::middleware('edition.cap:editions_write')->group(function () {
            Route::post('/editions/{event}/importer', [EventController::class, 'import'])->name('admin.evenements.import');
            Route::post('/editions/{event}/activer', [EventController::class, 'activate'])->name('admin.evenements.activate');
            Route::delete('/editions/{event}', [EventController::class, 'destroy'])->name('admin.evenements.destroy');
            Route::get('/editions/creer', [EventFormController::class, 'create'])->name('admin.evenements.create');
            Route::post('/editions/creer', [EventFormController::class, 'store'])->name('admin.evenements.store');
            // Anciennes URL de détail : ouvrir la page de modification sans exposer la route DELETE au navigateur.
            Route::get('/editions/{event}', fn (Event $event) => redirect()->route('admin.evenements.edit', $event))
                ->name('admin.evenements.show');
            Route::post('/editions/ia-assist', [EventFormController::class, 'aiAssist'])->name('admin.evenements.ai-assist');
            Route::post('/editions/ia-assist-json', [EventFormController::class, 'aiAssistJson'])->name('admin.evenements.ai-assist-json');
            Route::get('/editions/{event}/modifier', [EventFormController::class, 'edit'])->name('admin.evenements.edit');
            Route::put('/editions/{event}/modifier', [EventFormController::class, 'update'])->name('admin.evenements.update');
            Route::post('/editions/{event}/relecteurs/expertise', [EventFormController::class, 'saveReviewerExpertise'])->name('admin.evenements.reviewers.expertise');
            Route::post('/editions/{event}/relecteurs', [App\Http\Controllers\Admin\SubmissionController::class, 'storeReviewer'])->name('admin.evenements.reviewers.store');
            Route::post('/editions/{event}/relecteurs/{reviewer}/renvoyer', [App\Http\Controllers\Admin\SubmissionController::class, 'resendReviewerRegistration'])->name('admin.evenements.reviewers.resend');
            Route::delete('/editions/{event}/relecteurs/{reviewer}', [App\Http\Controllers\Admin\SubmissionController::class, 'removeReviewer'])->name('admin.evenements.reviewers.remove');
            Route::post('/editions/{event}/relecteurs/candidatures/{application}/accepter', [App\Http\Controllers\Admin\SubmissionController::class, 'acceptReviewerApplication'])->name('admin.evenements.reviewers.applications.accept');
            Route::get('/editions/{event}/relecteurs/personnes', [App\Http\Controllers\Admin\SubmissionController::class, 'reviewerPeople'])->name('admin.evenements.reviewers.people');
            Route::post('/editions/{event}/relecteurs/grille', [EventFormController::class, 'saveReviewGrid'])->name('admin.evenements.reviewers.criteria');
            Route::post('/editions/{event}/relecteurs/candidatures/options', [EventFormController::class, 'saveReviewerVolunteering'])->name('admin.evenements.reviewers.volunteering');
            Route::post('/editions/{event}/paiement', [EventFormController::class, 'savePayment'])->name('admin.evenements.payment');
            Route::post('/editions/{event}/visio', [EventFormController::class, 'saveMeetings'])->name('admin.evenements.meetings');
            Route::post('/editions/{event}/docspace', [EventFormController::class, 'saveDocSpace'])->name('admin.evenements.docspace');
            Route::post('/editions/{event}/traductions', [EventFormController::class, 'saveTranslations'])->name('admin.evenements.translations');
            Route::post('/editions/{event}/equipe', [EventFormController::class, 'attachTeamMember'])->name('admin.evenements.team.attach');
            Route::delete('/editions/{event}/equipe', [EventFormController::class, 'detachTeamMember'])->name('admin.evenements.team.detach');
            Route::get('/editions/{event}/formulaire', [SubmissionFormController::class, 'edit'])->name('admin.evenements.form');
            Route::put('/editions/{event}/formulaire', [SubmissionFormController::class, 'update'])->name('admin.evenements.form.update');
            // Règles & limites seules : elles vivent dans l'onglet Options du module Soumissions.
            Route::put('/editions/{event}/formulaire/regles', [SubmissionFormController::class, 'updateRules'])->name('admin.evenements.form.rules');
            Route::get('/editions/{event}/documents', [EventMediaController::class, 'index'])->name('admin.evenements.documents');
            Route::post('/editions/{event}/documents', [EventMediaController::class, 'store'])->name('admin.evenements.documents.store');
            Route::delete('/editions/{event}/documents/{medium}', [EventMediaController::class, 'destroy'])->name('admin.evenements.documents.destroy');
        });

        // Soumissions.
        Route::middleware('edition.cap:submissions_list')->group(function () {
            Route::get('/soumissions', [App\Http\Controllers\Admin\SubmissionController::class, 'index'])->name('admin.submissions.index');
            Route::get('/soumissions/export/csv', [App\Http\Controllers\Admin\SubmissionController::class, 'export'])->name('admin.submissions.export');
            Route::get('/soumissions/export/actes/{format}', [App\Http\Controllers\Admin\SubmissionController::class, 'bibliography'])->name('admin.submissions.bibliography');
            // Attribution automatique des relecteurs (route statique AVANT le joker {submission}).
            Route::get('/soumissions/attribution-auto', [App\Http\Controllers\Admin\SubmissionController::class, 'autoAssign'])->name('admin.submissions.auto-assign');
            Route::post('/soumissions/attribution-auto', [App\Http\Controllers\Admin\SubmissionController::class, 'autoAssignApply'])->name('admin.submissions.auto-assign.apply');
            // Relecteurs & expertise + règles d'évaluation : onglets du module
            // Soumissions (routes statiques AVANT le joker {submission}).
            Route::get('/soumissions/relecteurs', [App\Http\Controllers\Admin\SubmissionController::class, 'reviewers'])->name('admin.submissions.reviewers');
            Route::get('/soumissions/options', [App\Http\Controllers\Admin\SubmissionController::class, 'options'])->name('admin.submissions.options');
            Route::get('/soumissions/{submission}', [App\Http\Controllers\Admin\SubmissionController::class, 'show'])->name('admin.submissions.show');
            Route::post('/soumissions/{submission}/statut', [App\Http\Controllers\Admin\SubmissionController::class, 'applyStatus'])->name('admin.submissions.status');
            Route::post('/soumissions/{submission}/reponse-relecteurs', [App\Http\Controllers\Admin\SubmissionController::class, 'toggleRebuttal'])->name('admin.submissions.rebuttal.toggle');
            Route::post('/soumissions/{submission}/relecteur', [App\Http\Controllers\Admin\SubmissionController::class, 'assignReviewer'])->name('admin.submissions.review.assign');
            Route::delete('/soumissions/{submission}/relecteur/{review}', [App\Http\Controllers\Admin\SubmissionController::class, 'removeReview'])->name('admin.submissions.review.remove');
            Route::post('/soumissions/{submission}/plagiat', [App\Http\Controllers\Admin\SubmissionController::class, 'checkPlagiarism'])->name('admin.submissions.plagiarism');
        });

        // ⚠️⚠️ **TOUCHER AUX AUTEURS EST UN GESTE DE GESTIONNAIRE**, d'où
        // `editions_write` et non `submissions_list` : cette dernière ouvre la
        // LISTE des soumissions, et elle est portée aussi par l'évaluateur, le
        // secrétariat, la comptabilité, l'appui et l'accueil. Le rattachement
        // manuel vivait sous cette capacité trop large - un évaluateur pouvait
        // rattacher un auteur à un compte. La liste des auteurs est une donnée
        // scientifique : elle sort dans le livret et dans les attestations.
        Route::middleware('edition.cap:editions_write')->group(function () {
            Route::post('/soumissions/{submission}/auteurs', [App\Http\Controllers\Admin\SubmissionController::class, 'updateAuthors'])->name('admin.submissions.authors.update');
            // Rattachement manuel d'un auteur à un compte (correction d'un rapprochement).
            Route::post('/soumissions/{submission}/auteurs/{link}/rattacher', [App\Http\Controllers\Admin\SubmissionController::class, 'linkAuthor'])->name('admin.submissions.author.link');
            Route::post('/soumissions/{submission}/auteurs/{link}/detacher', [App\Http\Controllers\Admin\SubmissionController::class, 'detachAuthor'])->name('admin.submissions.author.detach');
        });

        Route::delete('/soumissions/{submission}', [App\Http\Controllers\Admin\SubmissionController::class, 'destroy'])
            ->middleware('edition.cap:editions_write')->name('admin.submissions.destroy');

        // Paiements.
        Route::middleware('edition.cap:payments')->group(function () {
            Route::get('/paiements', [App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('admin.payments.index');
            Route::get('/paiements/export/csv', [App\Http\Controllers\Admin\PaymentController::class, 'export'])->name('admin.payments.export');
            Route::delete('/paiements/{payment}', [App\Http\Controllers\Admin\PaymentController::class, 'destroy'])->name('admin.payments.destroy');
            Route::post('/paiements/{payment}/regler', [App\Http\Controllers\Admin\PaymentController::class, 'markPaid'])->name('admin.payments.mark-paid');
            Route::post('/paiements/{payment}/reverifier', [App\Http\Controllers\Admin\PaymentController::class, 'recheck'])->name('admin.payments.recheck');
            Route::post('/paiements/{payment}/rembourser', [App\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('admin.payments.refund');
            Route::get('/paiements/creer', [App\Http\Controllers\Admin\PaymentController::class, 'create'])->name('admin.payments.create');
            Route::post('/paiements/creer', [App\Http\Controllers\Admin\PaymentController::class, 'store'])->name('admin.payments.store');
            Route::get('/paiements-recu-exemple', [ReceiptController::class, 'sample'])->name('admin.payments.receipt.sample');
            Route::get('/paiements/{payment}/recu', ReceiptController::class)->name('admin.payments.receipt');
        });

        // Utilisateurs & rôles.
        Route::middleware('can:users_roles')->group(function () {
            Route::get('/utilisateurs', [UserController::class, 'index'])->name('admin.users.index');
            Route::get('/utilisateurs/en-ligne', [UserController::class, 'online'])->name('admin.users.online');
            Route::get('/utilisateurs/creer', [UserController::class, 'create'])->name('admin.users.create');
            Route::post('/utilisateurs', [UserController::class, 'store'])->name('admin.users.store');
            Route::put('/utilisateurs/matrice-acces', [UserController::class, 'updateAccess'])->name('admin.users.access');
            Route::get('/utilisateurs/{user}/modifier', [UserController::class, 'edit'])->name('admin.users.edit');
            Route::put('/utilisateurs/{user}', [UserController::class, 'update'])->name('admin.users.update');
            Route::post('/utilisateurs/{user}/verification', [UserController::class, 'resendVerification'])->name('admin.users.resend');
            Route::post('/utilisateurs/{user}/mot-de-passe', [UserController::class, 'sendPasswordReset'])->name('admin.users.password-reset');
            Route::delete('/utilisateurs/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
            // « Se connecter en tant que » : l'impersonation appartient à la page des
            // COMPTES, pas à celle des participants - c'est un compte qu'on emprunte,
            // pas une inscription. Le contrôleur revérifie `canImpersonate()`.
            Route::post('/utilisateurs/{user}/impersonate', [ImpersonationController::class, 'take'])->name('admin.users.impersonate');
        });

        // Participants.
        Route::middleware('edition.cap:participants')->group(function () {
            Route::get('/participants', [ParticipantController::class, 'index'])->name('admin.participants.index');
            Route::post('/participants/inviter-payes', [ParticipantController::class, 'inviteBulk'])->name('admin.participants.invite-bulk');
            Route::post('/participants/{participant}/inviter', [ParticipantController::class, 'invite'])->name('admin.participants.invite');
            Route::get('/participants/{participant}/apercu', [ParticipantController::class, 'preview'])->name('admin.participants.preview');
        });

        // E-mails.
        Route::middleware('edition.cap:emails')->group(function () {
            Route::get('/emails', [EmailController::class, 'index'])->name('admin.emails.index');
            Route::post('/emails/envoyer', [EmailController::class, 'send'])->name('admin.emails.send');
            Route::post('/emails/envoyer-dus', [EmailController::class, 'sendDue'])->name('admin.emails.send-due');
            Route::delete('/emails/{email}', [EmailController::class, 'cancel'])->name('admin.emails.cancel');
            Route::post('/emails/annuler-campagne', [EmailController::class, 'cancelBatch'])->name('admin.emails.cancel-batch');
            Route::post('/emails/relancer-campagne', [EmailController::class, 'retryBatch'])->name('admin.emails.retry-batch');
            Route::post('/emails/modeles/apercu', [EmailTemplateController::class, 'preview'])->name('admin.emails.templates.preview');
            Route::put('/emails/modeles', [EmailTemplateController::class, 'update'])->name('admin.emails.templates.update');
            Route::post('/emails/modeles/nouveau', [EmailTemplateController::class, 'store'])->name('admin.emails.templates.store');
            Route::delete('/emails/modeles/{key}', [EmailTemplateController::class, 'destroy'])->name('admin.emails.templates.destroy');
        });

        // Rapports (par événement).
        Route::middleware('edition.cap:view_dashboard')->group(function () {
            Route::get('/rapports', [ReportController::class, 'index'])->name('admin.reports.index');
            Route::post('/rapports/generer', [ReportController::class, 'generate'])->name('admin.reports.generate');
            Route::get('/rapports/{event}/excel', [ReportController::class, 'xlsx'])->name('admin.reports.xlsx');
            Route::get('/rapports/{event}/pdf', [ReportController::class, 'pdf'])->name('admin.reports.pdf');
            Route::get('/rapports/{event}/export/{dataset}/{format}', [ReportController::class, 'export'])->name('admin.reports.export');
            Route::get('/rapports/{event}/ia/{id}/telecharger', [ReportController::class, 'aiDownload'])->name('admin.reports.ai-download');
        });

        // Gestion de projet de l'événement (l'événement pilotée comme un projet) -
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

            // Documents de travail d'une tâche, rédigés à plusieurs.
            //
            // ⚠️⚠️ **LE DOCUMENT EST IMBRIQUÉ SOUS SA TÂCHE, ET CE N'EST PAS
            // COSMÉTIQUE.** `edition.cap:project` rattache la requête à son
            // événement en lisant `event_id` sur le premier modèle lié de la
            // route ; un document n'en porte pas, donc une route
            // `/projet/documents/{document}` tomberait en 403 pour tout le monde
            // sauf le super-administrateur.
            //
            // ⚠️ `scopeBindings()` exige en plus que le document appartienne à
            // CETTE tâche : sans lui, une tâche accessible plus l'identifiant
            // d'un document d'un AUTRE événement ouvriraient le second.
            Route::prefix('/projet/taches/{task}')->scopeBindings()->group(function () {
                Route::post('/documents', [TaskDocumentController::class, 'store'])->name('admin.project.documents.store');
                Route::get('/documents/{document}', [TaskDocumentController::class, 'edit'])->name('admin.project.documents.edit');
                Route::post('/documents/{document}/renouveler-lien-docspace', [TaskDocumentController::class, 'rotateLink'])->name('admin.project.documents.rotate-link');
                Route::get('/documents/{document}/telecharger', [TaskDocumentController::class, 'download'])->name('admin.project.documents.download');
                Route::put('/documents/{document}', [TaskDocumentController::class, 'rename'])->name('admin.project.documents.rename');
                Route::delete('/documents/{document}', [TaskDocumentController::class, 'destroy'])->name('admin.project.documents.destroy');
            });
        });

        // Programme / créneaux (structure de l'événement).
        Route::middleware('edition.cap:editions_read')->group(function () {
            // Module Programme : une page, un onglet par `?tab=`.
            Route::get('/programme', [ProgramController::class, 'index'])->name('admin.program.index');
            // ⚠️ Anciennes entrées conservées en redirection (liens et signets).
            Route::get('/programme/sessions', [ProgramController::class, 'sessions'])->name('admin.program.sessions');
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
            // Auto-complétion des personnes (modérateur/rapporteur) : rattache la
            // séance à un COMPTE existant par e-mail (sinon la liaison se perd).
            Route::get('/programme/personnes', [ProgramController::class, 'people'])->name('admin.program.people');
            // Fils de discussion des visios (onglet « Discussions ») : consultables
            // après la visio ET après l'événement (lecture directe de l'archive).
            Route::get('/programme/discussions/{session}', [ProgramController::class, 'discussion'])->name('admin.program.discussions.show');
            Route::get('/programme/discussions/{session}/export', [ProgramController::class, 'discussionExport'])->name('admin.program.discussions.export');
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
            Route::post('/programme/synthese', [ProgramController::class, 'saveSynthesis'])->name('admin.program.synthesis');
            Route::post('/programme/synthese/generer', [ProgramController::class, 'generateFromSynthesis'])->name('admin.program.synthesis.generate');
            Route::post('/programme/espaces', [ProgramController::class, 'storeSpace'])->name('admin.program.spaces.store');
            Route::put('/programme/espaces/{index}', [ProgramController::class, 'updateSpace'])->name('admin.program.spaces.update');
            Route::delete('/programme/espaces/{index}', [ProgramController::class, 'deleteSpace'])->name('admin.program.spaces.destroy');
        });

        // Annonces du déroulement (admin, gestionnaire, secrétariat).
        Route::middleware('edition.cap:announcements')->group(function () {
            Route::get('/annonces', [AnnouncementController::class, 'index'])->name('admin.announcements');
            Route::post('/editions/{event}/annonces', [AnnouncementController::class, 'store'])->name('admin.announcements.store');
            Route::delete('/editions/{event}/annonces/{announcement}', [AnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');
        });

        // Formations (module activable par événement).
        Route::middleware('edition.cap:formation')->group(function () {
            Route::get('/formations', [FormationController::class, 'index'])->name('admin.formations');
            Route::post('/editions/{event}/formations', [FormationController::class, 'store'])->name('admin.formations.store');
            Route::post('/formations/ia', [FormationController::class, 'aiAssist'])->name('admin.formations.ai');
            Route::get('/formations/{formation}', [FormationController::class, 'show'])->name('admin.formations.show');
            Route::put('/formations/{formation}', [FormationController::class, 'update'])->name('admin.formations.update');
            Route::delete('/formations/{formation}', [FormationController::class, 'destroy'])->name('admin.formations.destroy');
            Route::post('/formations/formateurs/rapprochement', [FormationController::class, 'lookupTrainer'])->name('admin.formations.trainers.lookup');
            Route::post('/formations/{formation}/formateurs', [FormationController::class, 'storeTrainer'])->name('admin.formations.trainers.store');
            Route::put('/formations/{formation}/formateurs/{trainer}', [FormationController::class, 'updateTrainer'])->name('admin.formations.trainers.update');
            Route::post('/formations/{formation}/formateurs/{trainer}/photo', [FormationController::class, 'updateTrainerPhoto'])->name('admin.formations.trainers.photo');
            Route::post('/formations/{formation}/formateurs/{trainer}/signature', [FormationController::class, 'updateTrainerSignature'])->name('admin.formations.trainers.signature');
            Route::delete('/formations/{formation}/formateurs/{trainer}', [FormationController::class, 'destroyTrainer'])->name('admin.formations.trainers.destroy');
            Route::post('/formations/{formation}/message', [FormationController::class, 'message'])->name('admin.formations.message');
            Route::post('/formations/{formation}/attestations', [FormationController::class, 'issueAttestations'])->name('admin.formations.attestations');
            Route::post('/formations/{formation}/visio', [FormationController::class, 'setMeeting'])->name('admin.formations.meeting');
        });

        // Documents (attestations, badges, livret, fiches).
        Route::middleware('edition.cap:documents')->group(function () {
            Route::get('/documents', [DocumentHubController::class, 'index'])->name('admin.documents.attestations');
            Route::post('/attestations/emettre', [DocumentHubController::class, 'issueType'])->name('admin.documents.attestations.issue');
            Route::post('/attestations/{attestation}/revoquer', [DocumentHubController::class, 'revoke'])->name('admin.documents.attestations.revoke');
            Route::post('/attestations/{attestation}/retablir', [DocumentHubController::class, 'restore'])->name('admin.documents.attestations.restore');
            Route::post('/attestations/options', [DocumentHubController::class, 'saveAttestationOptions'])->name('admin.documents.attestations.options');
            // Sortie PAPIER : le lot à imprimer, la liste d'émargement, et la trace
            // de ce qui a été remis. ⚠️ Ces exports portent sur les filtres de
            // l'écran (AttestationQuery) : on imprime ce qu'on regarde.
            Route::get('/attestations/lot.pdf', [AttestationExportController::class, 'pdf'])->name('admin.documents.attestations.lot');
            Route::get('/attestations/emargement.pdf', [AttestationExportController::class, 'emargement'])->name('admin.documents.attestations.emargement');
            Route::post('/attestations/{attestation}/remise', [AttestationExportController::class, 'deliver'])->name('admin.documents.attestations.deliver');
            Route::post('/livret/options', [DocumentHubController::class, 'saveLivretOptions'])->name('admin.documents.livret.options');
            Route::post('/livret/couverture', [DocumentHubController::class, 'createLivretCover'])->name('admin.documents.livret.cover.create');
            Route::delete('/livret/couverture', [DocumentHubController::class, 'deleteLivretCover'])->name('admin.documents.livret.cover.delete');
            Route::post('/livret/avant-propos-ia', [DocumentHubController::class, 'generateForewordAi'])->name('admin.documents.livret.foreword-ai');
            Route::post('/livret/apparence', [DocumentHubController::class, 'saveLivretApparence'])->name('admin.documents.livret.apparence');
            Route::post('/livret/modele', [DocumentHubController::class, 'saveLivretTemplate'])->name('admin.documents.livret.template');
            Route::post('/livret/modele/supprimer', [DocumentHubController::class, 'deleteLivretTemplate'])->name('admin.documents.livret.template.delete');
            Route::get('/livret/modele-base', [DocumentHubController::class, 'baseTemplate'])->name('admin.documents.livret.base-template');
            Route::get('/modeles', [TemplateDesignerController::class, 'index'])->name('admin.documents.templates');
            Route::get('/modeles/apercu', [TemplateDesignerController::class, 'preview'])->name('admin.documents.templates.preview');
            Route::post('/modeles/creer', [TemplateDesignerController::class, 'create'])->name('admin.documents.templates.create');
            Route::delete('/modeles/{template}', [TemplateDesignerController::class, 'destroy'])->name('admin.documents.templates.destroy');
            Route::post('/modeles/generer', [TemplateDesignerController::class, 'generate'])->name('admin.documents.templates.generate');
            Route::post('/modeles/enregistrer', [TemplateDesignerController::class, 'save'])->name('admin.documents.templates.save');
            Route::post('/modeles/image', [TemplateDesignerController::class, 'upload'])->name('admin.documents.templates.upload');
            Route::get('/editions/{event}/livret', [DocumentController::class, 'livret'])->name('admin.documents.livret');
            Route::get('/editions/{event}/badges', [DocumentController::class, 'badges'])->name('admin.documents.badges');
            Route::get('/editions/{event}/badges-tous', [DocumentController::class, 'badgesAll'])->name('admin.documents.badges.all');
            Route::post('/editions/{event}/badges-selection', [DocumentController::class, 'badgesSelection'])->name('admin.documents.badges.selection');
            Route::post('/editions/{event}/badges-export', [DocumentController::class, 'startBadgeExport'])->name('admin.documents.badges.export.start');
            Route::get('/editions/{event}/badges-export/{export}/statut', [DocumentController::class, 'badgeExportStatus'])->name('admin.documents.badges.export.status');
            Route::get('/editions/{event}/badges-export/{export}/telecharger', [DocumentController::class, 'downloadBadgeExport'])->name('admin.documents.badges.export.download');
            Route::get('/editions/{event}/fiches-session', [DocumentController::class, 'sessionSheets'])->name('admin.documents.sessions');
            Route::get('/editions/{event}/pv-seances', [DocumentController::class, 'sessionPv'])->name('admin.documents.session-pv');
            Route::get('/editions/{event}/mise-en-page-seances', [DocumentController::class, 'sessionLayout'])->name('admin.documents.session-layout');
            Route::put('/editions/{event}/mise-en-page-seances', [DocumentController::class, 'saveSessionLayout'])->name('admin.documents.session-layout.save');
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
            // ⚠️ Les agents IA sont un ONGLET des réglages, plus une page : la
            // redirection reste pour les signets et les liens déjà envoyés.
            Route::permanentRedirect('/reglages/agents-ia', '/admin/reglages?tab=agents-ia');
            Route::put('/reglages/agents-ia', [SettingAgentController::class, 'update'])->name('admin.settings.agents.update');
        });

        // Copilote IA (outil d'aide, décision humaine).
        Route::get('/assistant-ia', [AiAssistantController::class, 'page'])->middleware('edition.cap:view_dashboard')->name('admin.ai');
        Route::post('/assistant-ia/demander', [AiAssistantController::class, 'ask'])->middleware('edition.cap:view_dashboard')->name('admin.ai.ask');
        Route::post('/assistant-ia/executer', [AiAssistantController::class, 'execute'])->middleware('edition.cap:view_dashboard')->name('admin.ai.execute');
    });
});
