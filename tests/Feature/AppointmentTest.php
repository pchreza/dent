<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    private Patient $patient;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::query()->create(['code' => 'APT-001', 'name' => 'کلینیک نوبت', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'appointment_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
        $this->branch = Branch::query()->create(['tenant_id' => $this->tenant->id, 'code' => 'APT-BRANCH', 'name' => 'شعبه نوبت']);
        $this->patient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000001',
            'first_name' => 'سارا',
            'last_name' => 'احمدی',
            'national_id' => '0012345678',
            'mobile' => '09123456789',
            'status' => 'active',
        ]);
    }

    public function test_manager_can_create_appointment_and_see_it_in_jalali_calendar(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/appointments', [
                'patient_id' => $this->patient->id,
                'branch_id' => $this->branch->id,
                'title' => 'ویزیت اولیه',
                'appointment_type' => 'معاینه',
                'starts_at' => '2025-08-09 10:00:00',
                'ends_at' => '2025-08-09 10:30:00',
            ]);

        $response->assertRedirect('/clinic/calendar');
        $this->assertDatabaseHas('appointments', [
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'status' => 'scheduled',
        ]);
        $this->assertDatabaseHas('appointment_status_history', ['to_status' => 'scheduled']);

        $calendar = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/calendar?week=2025-08-12');

        $calendar->assertOk();
        $calendar->assertSee('ویزیت اولیه');
        $calendar->assertSee('1404/05/18');
    }

    public function test_overlapping_appointment_is_rejected(): void
    {
        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/appointments', [
                'patient_id' => $this->patient->id,
                'branch_id' => $this->branch->id,
                'title' => 'نوبت اول',
                'starts_at' => '2025-08-09 10:00:00',
                'ends_at' => '2025-08-09 10:30:00',
            ]);

        $response = $this->from('/clinic/appointments/create')
            ->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/appointments', [
                'patient_id' => $this->patient->id,
                'branch_id' => $this->branch->id,
                'title' => 'نوبت دوم',
                'starts_at' => '2025-08-09 10:15:00',
                'ends_at' => '2025-08-09 10:45:00',
            ]);

        $response->assertRedirect('/clinic/appointments/create');
        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseCount('appointments', 1);
    }
}
