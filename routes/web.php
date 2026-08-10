<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LogoutController;
use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Http\Controllers\Admin\ReceiptController;
use App\Livewire\Account\Payment\Pay as AccountPay;
use App\Livewire\Account\Dashboard;
use App\Livewire\Account\ForgotPassword;
use App\Livewire\Account\Login;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Register;
use App\Livewire\Account\ResetPassword;
use App\Livewire\Account\Submissions\Create as SubmissionCreate;
use App\Livewire\Account\Submissions\Index as SubmissionIndex;
use App\Livewire\Account\Submissions\Show as SubmissionShow;
use App\Livewire\Account\VerifyEmailNotice;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Editions\Form as EditionForm;
use App\Livewire\Admin\Editions\Index as EditionIndex;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Admin\Payments\Index as AdminPaymentIndex;
use App\Livewire\Admin\Payments\Record as AdminPaymentRecord;
use App\Livewire\Admin\Submissions\Index as AdminSubmissionIndex;
use App\Livewire\Admin\Submissions\Show as AdminSubmissionShow;
use App\Livewire\Public\ColloquePage;
use App\Livewire\Reviewer\Evaluate as ReviewerEvaluate;
use App\Livewire\Reviewer\Portal as ReviewerPortal;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Page publique d'une édition de colloque.
Route::get('/colloque/{slug}', ColloquePage::class)->name('public.colloque');

// Portail relecteur (accès par jeton, sans compte).
Route::get('/relecture/{token}', ReviewerPortal::class)->name('reviewer.portal');
Route::get('/relecture/{token}/{review}', ReviewerEvaluate::class)->name('reviewer.evaluate');

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

Route::middleware('auth:account')->group(function () {
    Route::get('/mon-compte', Dashboard::class)->name('account.dashboard');
    Route::get('/mon-profil', Profile::class)->name('account.profile');

    // Communications
    Route::get('/mes-communications', SubmissionIndex::class)->name('account.submissions.index');
    Route::get('/mes-communications/{submission}', SubmissionShow::class)->name('account.submissions.show');
    Route::get('/colloque/{slug}/soumettre', SubmissionCreate::class)->name('account.submissions.create');

    // Paiement des frais
    Route::get('/mon-compte/paiement/{edition}', AccountPay::class)->name('account.payment');

    // Vérification d'e-mail
    Route::get('/verification-email', VerifyEmailNotice::class)->name('account.verification.notice');
    Route::get('/verifier-email/{id}/{hash}', EmailVerificationController::class)
        ->middleware('signed')
        ->name('account.verification.verify');

    Route::post('/deconnexion', LogoutController::class)->name('account.logout');
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

        Route::get('/editions', EditionIndex::class)->name('admin.editions.index');
        Route::get('/editions/creer', EditionForm::class)->name('admin.editions.create');
        Route::get('/editions/{edition}/modifier', EditionForm::class)->name('admin.editions.edit');

        Route::get('/soumissions', AdminSubmissionIndex::class)->name('admin.submissions.index');
        Route::get('/soumissions/{submission}', AdminSubmissionShow::class)->name('admin.submissions.show');

        Route::get('/paiements', AdminPaymentIndex::class)->name('admin.payments.index');
        Route::get('/paiements/creer', AdminPaymentRecord::class)->name('admin.payments.create');
        Route::get('/paiements/{payment}/recu', ReceiptController::class)->name('admin.payments.receipt');

        Route::post('/deconnexion', AdminLogoutController::class)->name('admin.logout');
    });
});
