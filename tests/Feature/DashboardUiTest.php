<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DashboardUiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow('2026-08-13 09:00:00');

        $this->tenant = Tenant::query()->create([
            'code' => 'UI-001',
            'name' => 'کلینیک رابط کاربری',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
        $this->manager = User::factory()->create(['username' => 'dashboard_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_displays_real_tenant_scoped_metrics_and_upcoming_appointment(): void
    {
        $patient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-UI-0001',
            'first_name' => 'سارا',
            'last_name' => 'طراحی',
            'national_id' => '0012345678',
            'mobile' => '09121111111',
            'status' => 'active',
        ]);

        Appointment::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'title' => 'ویزیت کنترل',
            'status' => 'scheduled',
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(3),
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);

        Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $patient->id,
            'invoice_no' => 'INV-UI-0001',
            'status' => 'issued',
            'issue_date' => now()->toDateString(),
            'subtotal' => 200000,
            'discount' => 0,
            'total' => 200000,
            'paid_total' => 50000,
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('<html lang="fa" dir="rtl">', false)
            ->assertSee('بیماران ثبت‌شده')
            ->assertSee('نوبت‌های امروز')
            ->assertSee('ماندهٔ فاکتورهای باز')
            ->assertSee('سارا طراحی')
            ->assertSee('ویزیت کنترل')
            ->assertSee('برنامه‌ریزی‌شده')
            ->assertSee('1405/05/22')
            ->assertSee('150,000')
            ->assertSee('data-sidebar', false)
            ->assertSee('aria-label="ناوبری اصلی"', false)
            ->assertSee('منوی کاربر');
    }
}
