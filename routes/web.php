<?php

use App\Http\Controllers\Admin\ContestController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Judge\VotingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (judges only — admins don't need this)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Judge: Voting (admin is blocked inside controller)
    Route::get('/contests/{contest}/vote', [VotingController::class, 'index'])->name('judge.voting.index');
    Route::get('/contests/{contest}/vote/{submission}', [VotingController::class, 'show'])->name('judge.voting.show');
    Route::post('/contests/{contest}/vote/{submission}', [VotingController::class, 'vote'])->name('judge.voting.vote');
    Route::post('/contests/{contest}/honorable-mention/{submission}', [VotingController::class, 'honorableMention'])->name('judge.voting.hm');

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
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
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
