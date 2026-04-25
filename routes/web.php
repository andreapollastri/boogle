<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\ExceptionController;
use App\Http\Controllers\Panel\GroupController;
use App\Http\Controllers\Panel\ProfileController;
use App\Http\Controllers\Panel\ProjectController;
use App\Http\Controllers\Panel\UserController;
use Illuminate\Support\Facades\Route;

Route::view('api/docs', 'api.docs')->name('api.docs');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::get('forgot-password', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('reset-password', [PasswordController::class, 'reset'])->name('password.update');
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Panel (authenticated) at root
Route::middleware(['auth', 'twofactor'])->name('panel.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('projects', ProjectController::class);
    Route::get('projects/{project}/installation', [ProjectController::class, 'installation'])
        ->name('projects.installation');
    Route::post('projects/{project}/token', [ProjectController::class, 'regenerateToken'])
        ->name('projects.token');

    Route::prefix('projects/{project}')->name('projects.')->group(function () {
        Route::get('exceptions', [ExceptionController::class, 'index'])->name('exceptions.index');
        Route::get('exceptions/{exception}', [ExceptionController::class, 'show'])->name('exceptions.show');
        Route::patch('exceptions/{exception}/status', [ExceptionController::class, 'markAs'])->name('exceptions.mark');
        Route::patch('exceptions/{exception}/publish', [ExceptionController::class, 'togglePublish'])->name('exceptions.publish');
        Route::post('exceptions/bulk', [ExceptionController::class, 'bulkAction'])->name('exceptions.bulk');
    });

    Route::middleware('admin')->resource('groups', GroupController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::middleware('admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::patch('{user}', [UserController::class, 'update'])->name('update');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
    });

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('profile/notifications', [ProfileController::class, 'updateNotifications'])->name('profile.notifications');
    Route::post('profile/two-factor/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.two-factor.enable');
    Route::post('profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.two-factor.confirm');
    Route::delete('profile/two-factor/setup', [ProfileController::class, 'cancelTwoFactorSetup'])->name('profile.two-factor.cancel');
    Route::delete('profile/two-factor', [ProfileController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');
    Route::post('profile/api-tokens', [ProfileController::class, 'createApiToken'])->name('profile.api-tokens.store');
    Route::delete('profile/api-tokens/{tokenId}', [ProfileController::class, 'revokeApiToken'])->name('profile.api-tokens.destroy');
});

// Public exception view
Route::get('e/{hash}', [ExceptionController::class, 'publicView'])->name('exceptions.public');
