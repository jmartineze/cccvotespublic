<?php

use App\Http\Controllers\Admin\ContestController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Judge\VotingController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware(['guest', 'throttle:20,1']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Password reset (email link → new password)
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update')->middleware('throttle:6,1');
});

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Gated media (no direct /storage links — tenant scope enforced)
    Route::get('/media/submission-image/{image}', [MediaController::class, 'submissionImage'])->name('media.submission-image');
    Route::get('/media/contest-cover/{contest}', [MediaController::class, 'contestCover'])->name('media.contest-cover');

    // Judge/Admin view switch (tenant_admin + co_admin)
    Route::post('/mode/toggle', [ModeController::class, 'toggle'])->name('mode.toggle');
    Route::post('/mode/tenant', [ModeController::class, 'setTenant'])->name('mode.tenant');

    // Profile — every signed-in user edits their own name / username / email / password
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Judge: Voting (admin is blocked inside controller)
    Route::get('/contests/{contest}/vote', [VotingController::class, 'index'])->name('judge.voting.index');
    Route::get('/contests/{contest}/vote/{submission}', [VotingController::class, 'show'])->name('judge.voting.show');
    Route::post('/contests/{contest}/vote/{submission}', [VotingController::class, 'vote'])->name('judge.voting.vote')->middleware('throttle:60,1');
    Route::post('/contests/{contest}/honorable-mention/{submission}', [VotingController::class, 'honorableMention'])->name('judge.voting.hm')->middleware('throttle:60,1');
    Route::post('/contests/{contest}/special-prize/{prize}/{submission}', [VotingController::class, 'specialPrize'])->name('judge.voting.special-prize')->middleware('throttle:120,1');

    // Results
    Route::get('/results', [ResultsController::class, 'index'])->name('results.index');
    Route::get('/results/{contest}', [ResultsController::class, 'show'])->name('results.show');

    // Admin
    Route::middleware(AdminMiddleware::class)->prefix('admin')->name('admin.')->group(function () {
        // Contests
        Route::resource('contests', ContestController::class)->except(['show']);
        // Submissions
        Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
        Route::get('submissions/create', [SubmissionController::class, 'create'])->name('submissions.create');
        Route::post('submissions', [SubmissionController::class, 'store'])->name('submissions.store');
        Route::get('submissions/{submission}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');
        Route::put('submissions/{submission}', [SubmissionController::class, 'update'])->name('submissions.update');
        Route::delete('submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');
        // Judges / Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::post('users/invite', [UserController::class, 'invite'])->name('users.invite');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::post('users/{user}/promote', [UserController::class, 'promote'])->name('users.promote');
        Route::post('users/{user}/demote', [UserController::class, 'demote'])->name('users.demote');
    });

    // Super Admin
    Route::middleware(SuperAdminMiddleware::class)->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');
        Route::post('tenants/{tenant}/reset-password', [TenantController::class, 'resetPassword'])->name('tenants.reset-password');
    });
});
