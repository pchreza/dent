<?php

declare(strict_types=1);

use App\Http\Controllers\ActiveTenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\TenantController;
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
        Route::post('/active-tenant/{tenantId}', [ActiveTenantController::class, 'store'])
            ->whereNumber('tenantId')
            ->name('active-tenant.store');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::prefix('clinic')->name('branches.')->middleware('permission:branches.view')->group(function (): void {
            Route::get('/branches', [BranchController::class, 'index'])->name('index');
            Route::get('/branches/create', [BranchController::class, 'create'])
                ->middleware('permission:branches.create')
                ->name('create');
            Route::post('/branches', [BranchController::class, 'store'])
                ->middleware('permission:branches.create')
                ->name('store');
        });
    });

    Route::middleware(['auth', 'system_admin'])->prefix('admin')->name('tenants.')->group(function (): void {
        Route::get('/tenants', [TenantController::class, 'index'])->name('index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('create');
        Route::post('/tenants', [TenantController::class, 'store'])->name('store');
    });
});
