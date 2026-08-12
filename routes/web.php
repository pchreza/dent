<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Support\InstallationState;
use Illuminate\Support\Facades\Route;

Route::get('/', function (InstallationState $installationState) {
    return redirect()->route($installationState->isInstalled() ? 'login' : 'install.index');
})->name('home');

Route::middleware('not_installed')->group(function (): void {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install', [InstallController::class, 'store'])
        ->middleware('throttle:install')
        ->name('install.store');
});

Route::middleware('installed')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(['guest', 'throttle:login'])
        ->name('login.store');

    Route::middleware(['auth', 'tenant'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
