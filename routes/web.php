<?php

use App\Http\Controllers\Account\EmailVerificationController;
use App\Http\Controllers\Account\LogoutController;
use App\Http\Controllers\Admin\LogoutController as AdminLogoutController;
use App\Livewire\Account\Dashboard;
use App\Livewire\Account\ForgotPassword;
use App\Livewire\Account\Login;
use App\Livewire\Account\Profile;
use App\Livewire\Account\Register;
use App\Livewire\Account\ResetPassword;
use App\Livewire\Account\Submissions\Create as SubmissionCreate;
use App\Livewire\Account\Submissions\Index as SubmissionIndex;
use App\Livewire\Account\VerifyEmailNotice;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Editions\Form as EditionForm;
use App\Livewire\Admin\Editions\Index as EditionIndex;
use App\Livewire\Admin\Login as AdminLogin;
use App\Livewire\Public\ColloquePage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Page publique d'une édition de colloque.
Route::get('/colloque/{slug}', ColloquePage::class)->name('public.colloque');

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
    Route::get('/colloque/{slug}/soumettre', SubmissionCreate::class)->name('account.submissions.create');

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

        Route::post('/deconnexion', AdminLogoutController::class)->name('admin.logout');
    });
});
