<?php

declare(strict_types=1);

use App\Http\Controllers\ActiveTenantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\QrRegistrationRequestController;
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
    Route::get('/register/{tenantCode}', [PublicRegistrationController::class, 'create'])
        ->where('tenantCode', '[A-Za-z0-9_-]+')
        ->name('public.registration');
    Route::post('/register/{tenantCode}', [PublicRegistrationController::class, 'store'])
        ->where('tenantCode', '[A-Za-z0-9_-]+')
        ->middleware('throttle:qr-registration')
        ->name('public.registration.store');
    Route::get('/register/{tenantCode}/success', [PublicRegistrationController::class, 'success'])
        ->where('tenantCode', '[A-Za-z0-9_-]+')
        ->name('public.registration.success');

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

        Route::prefix('clinic')->name('patients.')->middleware('permission:patients.view')->group(function (): void {
            Route::get('/patients', [PatientController::class, 'index'])->name('index');
            Route::get('/patients/{patientId}', [PatientController::class, 'show'])->whereNumber('patientId')->name('show');
        });

        Route::prefix('clinic')->name('qr-requests.')->middleware('permission:patients.create')->group(function (): void {
            Route::get('/qr-requests', [QrRegistrationRequestController::class, 'index'])->name('index');
            Route::post('/qr-requests/{registrationRequestId}/approve', [QrRegistrationRequestController::class, 'approve'])
                ->whereNumber('registrationRequestId')->name('approve');
            Route::post('/qr-requests/{registrationRequestId}/reject', [QrRegistrationRequestController::class, 'reject'])
                ->whereNumber('registrationRequestId')->name('reject');
        });
    });

    Route::middleware(['auth', 'system_admin'])->prefix('admin')->name('tenants.')->group(function (): void {
        Route::get('/tenants', [TenantController::class, 'index'])->name('index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('create');
        Route::post('/tenants', [TenantController::class, 'store'])->name('store');
    });
});
