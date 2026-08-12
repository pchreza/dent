<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TreatmentPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TreatmentPlanTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::query()->create(['code' => 'TRT-001', 'name' => 'کلینیک درمان', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'treatment_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
        $this->patient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000001',
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'national_id' => '0012345678',
            'mobile' => '09120000000',
            'status' => 'active',
        ]);
    }

    public function test_manager_can_add_custom_treatment_stage(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/treatment-stages', [
                'code' => 'custom_veneer',
                'name' => 'لمینت اختصاصی',
                'category' => 'cosmetic',
                'sort_order' => 80,
                'color' => '#123456',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('treatment_stage_definitions', [
            'tenant_id' => $this->tenant->id,
            'code' => 'custom_veneer',
            'name' => 'لمینت اختصاصی',
        ]);
    }

    public function test_manager_can_create_treatment_plan_for_patient(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/treatment-plans', [
                'patient_id' => $this->patient->id,
                'title' => 'طرح درمان ترمیمی',
                'status' => 'active',
                'started_on' => '2025-08-12',
                'notes' => 'پرکردن و پیگیری عصب‌کشی.',
            ]);

        $response->assertRedirect('/clinic/patients/'.$this->patient->id);
        $this->assertDatabaseHas('treatment_plans', [
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'title' => 'طرح درمان ترمیمی',
            'status' => 'active',
        ]);
        self::assertSame(1, TreatmentPlan::query()->where('tenant_id', $this->tenant->id)->count());
    }
}
