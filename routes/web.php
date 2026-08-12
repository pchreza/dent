<?php

declare(strict_types=1);

use App\Http\Controllers\ActiveTenantController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClinicalFieldDefinitionController;
use App\Http\Controllers\ClinicUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DentalChartController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientClinicalFieldValueController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientPasswordController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\PatientTenantSelectionController;
use App\Http\Controllers\PlatformSettingsController;
use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\QrRegistrationRequestController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TreatmentPlanController;
use App\Http\Controllers\TreatmentPlanItemController;
use App\Http\Controllers\TreatmentStageController;
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

    Route::middleware('auth')->prefix('patient')->name('patient.')->group(function (): void {
        Route::get('/choose-clinic', [PatientTenantSelectionController::class, 'index'])->name('tenants.index');
        Route::post('/choose-clinic/{tenantId}', [PatientTenantSelectionController::class, 'store'])
            ->whereNumber('tenantId')
            ->name('tenants.store');
    });

    Route::middleware(['auth', 'tenant', 'patient_portal'])->prefix('patient')->name('patient.')->group(function (): void {
        Route::get('/password/change', [PatientPasswordController::class, 'edit'])->name('password.edit');
        Route::post('/password/change', [PatientPasswordController::class, 'update'])->name('password.update');
        Route::get('/dashboard', [PatientPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/appointments', [PatientPortalController::class, 'appointments'])->name('appointments');
        Route::get('/treatment-plans', [PatientPortalController::class, 'treatmentPlans'])->name('treatment-plans');
        Route::get('/invoices', [PatientPortalController::class, 'invoices'])->name('invoices');
        Route::get('/notifications', [PatientPortalController::class, 'notifications'])->name('notifications');
    });

    Route::middleware(['auth', 'tenant', 'staff_portal'])->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::post('/active-tenant/{tenantId}', [ActiveTenantController::class, 'store'])
            ->whereNumber('tenantId')
            ->name('active-tenant.store');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::prefix('clinic')->name('clinic-users.')->middleware('permission:users.view')->group(function (): void {
            Route::get('/users', [ClinicUserController::class, 'index'])->name('index');
            Route::get('/users/create', [ClinicUserController::class, 'create'])->middleware('permission:users.create')->name('create');
            Route::post('/users', [ClinicUserController::class, 'store'])->middleware('permission:users.create')->name('store');
        });

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
            Route::post('/patients/{patientId}/clinical-fields', [PatientClinicalFieldValueController::class, 'store'])
                ->whereNumber('patientId')
                ->middleware('permission:clinical.update')
                ->name('clinical-fields.store');
        });

        Route::prefix('clinic')->name('dental-chart.')->middleware('permission:dentistry.view')->group(function (): void {
            Route::get('/patients/{patientId}/dental-chart', [DentalChartController::class, 'show'])
                ->whereNumber('patientId')
                ->name('show');
            Route::post('/patients/{patientId}/dental-chart', [DentalChartController::class, 'store'])
                ->whereNumber('patientId')
                ->middleware('permission:dentistry.update')
                ->name('store');
        });

        Route::prefix('clinic')->name('clinical-fields.')->middleware('permission:clinical.update')->group(function (): void {
            Route::get('/clinical-fields', [ClinicalFieldDefinitionController::class, 'index'])->name('index');
            Route::post('/clinical-fields', [ClinicalFieldDefinitionController::class, 'store'])->name('store');
            Route::patch('/clinical-fields/{definitionId}', [ClinicalFieldDefinitionController::class, 'update'])
                ->whereNumber('definitionId')
                ->name('update');
        });

        Route::prefix('clinic')->name('calendar.')->middleware('permission:scheduling.view')->group(function (): void {
            Route::get('/calendar', [CalendarController::class, 'index'])->name('index');
        });

        Route::prefix('clinic')->name('appointments.')->middleware('permission:scheduling.create')->group(function (): void {
            Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('create');
            Route::post('/appointments', [AppointmentController::class, 'store'])->name('store');
        });

        Route::prefix('clinic')->name('treatment-stages.')->middleware('permission:treatments.update')->group(function (): void {
            Route::get('/treatment-stages', [TreatmentStageController::class, 'index'])->name('index');
            Route::post('/treatment-stages', [TreatmentStageController::class, 'store'])->name('store');
        });

        Route::prefix('clinic')->name('notifications.')->group(function (): void {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('index');
            Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markRead'])->whereNumber('notificationId')->name('read');
        });

        Route::prefix('clinic')->name('invoices.')->middleware('permission:finance.view')->group(function (): void {
            Route::get('/invoices', [InvoiceController::class, 'index'])->name('index');
            Route::get('/invoices/{invoiceId}', [InvoiceController::class, 'show'])->whereNumber('invoiceId')->name('show');
        });

        Route::prefix('clinic')->name('invoices.')->middleware('permission:finance.create')->group(function (): void {
            Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('create');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('store');
            Route::post('/invoices/{invoiceId}/payments', [InvoiceController::class, 'storePayment'])->whereNumber('invoiceId')->name('payments.store');
        });

        Route::prefix('clinic')->name('treatment-plans.')->middleware('permission:treatments.create')->group(function (): void {
            Route::get('/patients/{patientId}/treatment-plans/create', [TreatmentPlanController::class, 'create'])->whereNumber('patientId')->name('create');
            Route::post('/treatment-plans', [TreatmentPlanController::class, 'store'])->name('store');
        });

        Route::prefix('clinic')->name('treatment-plan-items.')->middleware('permission:treatments.update')->group(function (): void {
            Route::patch('/treatment-plan-items/{itemId}/status', [TreatmentPlanItemController::class, 'updateStatus'])
                ->whereNumber('itemId')
                ->name('status.update');
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
        Route::get('/settings/appearance', [PlatformSettingsController::class, 'appearance'])->name('admin.settings.appearance');
        Route::post('/settings/appearance', [PlatformSettingsController::class, 'updateAppearance'])->name('admin.settings.appearance.update');
    });
});
