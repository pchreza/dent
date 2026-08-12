<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ClinicalFieldDefinition;
use App\Models\DentalChartEntry;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\TreatmentStageDefinition;
use App\Models\User;
use App\Support\DentalToothPresenter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdvancedClinicalRecordTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private User $manager;

    private Patient $patient;

    private Patient $otherPatient;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::query()->create(['code' => 'CLIN-001', 'name' => 'کلینیک اول', 'status' => 'active', 'plan_code' => 'free']);
        $this->otherTenant = Tenant::query()->create(['code' => 'CLIN-002', 'name' => 'کلینیک دوم', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'clinical_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);

        $this->patient = $this->makePatient($this->tenant, 'P-CLIN-001', '09121111111');
        $this->otherPatient = $this->makePatient($this->otherTenant, 'P-CLIN-002', '09122222222');
    }

    public function test_manager_can_define_and_save_a_custom_clinical_field(): void
    {
        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/clinical-fields', [
                'key' => 'smoking_status',
                'label' => 'وضعیت مصرف دخانیات',
                'field_type' => 'select',
                'options_text' => "ندارد\nدارد",
                'is_required' => true,
                'sort_order' => 10,
            ])
            ->assertRedirect();

        $definition = ClinicalFieldDefinition::query()->where('tenant_id', $this->tenant->id)->firstOrFail();
        self::assertSame(['ندارد', 'دارد'], $definition->options);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/patients/'.$this->patient->id.'/clinical-fields', [
                'fields' => [$definition->id => 'ندارد'],
            ])
            ->assertRedirect();

        self::assertSame(
            'ندارد',
            $this->patient->clinicalFieldValues()->firstOrFail()->value['value'],
        );
    }

    public function test_manager_can_append_dental_chart_history_and_cannot_access_other_tenant_patient(): void
    {
        $session = ['active_tenant_id' => $this->tenant->id];

        $this->actingAs($this->manager)
            ->withSession($session)
            ->post('/clinic/patients/'.$this->patient->id.'/dental-chart', [
                'tooth_code' => '16',
                'surface_code' => 'O',
                'status_code' => 'caries',
                'note' => 'پوسیدگی اولیه.',
            ])
            ->assertRedirect();

        $this->actingAs($this->manager)
            ->withSession($session)
            ->post('/clinic/patients/'.$this->patient->id.'/dental-chart', [
                'tooth_code' => '16',
                'surface_code' => 'O',
                'status_code' => 'restored',
                'note' => 'ترمیم انجام شد.',
            ])
            ->assertRedirect();

        self::assertSame(2, DentalChartEntry::query()->where('patient_id', $this->patient->id)->count());
        $this->assertDatabaseHas('dental_chart_entries', [
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'tooth_code' => '16',
            'surface_code' => 'O',
            'status_code' => 'restored',
        ]);

        $this->actingAs($this->manager)
            ->withSession($session)
            ->get('/clinic/patients/'.$this->otherPatient->id.'/dental-chart')
            ->assertNotFound();
    }

    public function test_receptionist_cannot_record_dental_chart_entry(): void
    {
        $receptionist = User::factory()->create(['username' => 'clinical_receptionist']);
        $role = Role::query()->where('code', 'receptionist')->firstOrFail();
        $this->tenant->users()->attach($receptionist->id, ['role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($receptionist)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post('/clinic/patients/'.$this->patient->id.'/dental-chart', [
                'tooth_code' => '16',
                'surface_code' => 'O',
                'status_code' => 'caries',
            ])
            ->assertForbidden();
    }

    public function test_manager_can_change_item_status_and_history_is_retained(): void
    {
        $stage = TreatmentStageDefinition::query()->where('code', 'filling')->firstOrFail();
        $plan = TreatmentPlan::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'title' => 'طرح تست وضعیت',
            'status' => 'active',
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        $item = TreatmentPlanItem::query()->create([
            'tenant_id' => $this->tenant->id,
            'treatment_plan_id' => $plan->id,
            'stage_id' => $stage->id,
            'tooth_code' => '26',
            'surface_code' => 'M',
            'status' => 'planned',
            'priority' => 'normal',
            'sort_order' => 0,
        ]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->patch('/clinic/treatment-plan-items/'.$item->id.'/status', [
                'status' => 'in_progress',
                'reason' => 'شروع درمان',
            ])
            ->assertRedirect();

        self::assertSame('in_progress', $item->fresh()->status);
        $this->assertDatabaseHas('treatment_plan_item_status_history', [
            'tenant_id' => $this->tenant->id,
            'treatment_plan_item_id' => $item->id,
            'from_status' => 'planned',
            'to_status' => 'in_progress',
            'reason' => 'شروع درمان',
        ]);
    }

    public function test_fdi_presenter_describes_anatomical_tooth_in_persian(): void
    {
        $tooth = DentalToothPresenter::present('16');

        self::assertSame('آسیای بزرگ اول، فک بالا، سمت راست بیمار', $tooth['display_name']);
        self::assertSame('FDI 16', $tooth['fdi']);
        self::assertFalse($tooth['is_primary']);
        self::assertSame('molar', $tooth['placement']['family']);
    }

    public function test_minimal_dental_status_page_shows_latest_entries_and_selected_quick_entry(): void
    {
        $entry = DentalChartEntry::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'tooth_code' => '16',
            'surface_code' => 'O',
            'status_code' => 'caries',
            'note' => 'پوسیدگی سطح جونده',
            'recorded_by' => $this->manager->id,
        ]);
        $stage = TreatmentStageDefinition::query()->where('code', 'implant')->firstOrFail();
        $plan = TreatmentPlan::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'title' => 'طرح ایمپلنت',
            'status' => 'active',
            'created_by' => $this->manager->id,
            'updated_by' => $this->manager->id,
        ]);
        $item = TreatmentPlanItem::query()->create([
            'tenant_id' => $this->tenant->id,
            'treatment_plan_id' => $plan->id,
            'stage_id' => $stage->id,
            'tooth_code' => '36',
            'surface_code' => 'all',
            'status' => 'in_progress',
            'priority' => 'high',
            'sort_order' => 0,
        ]);
        $item->statusHistory()->create([
            'tenant_id' => $this->tenant->id,
            'from_status' => 'approved',
            'to_status' => 'in_progress',
            'reason' => 'شروع درمان',
            'changed_by' => $this->manager->id,
        ]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/patients/'.$this->patient->id.'/dental-chart?tooth=16')
            ->assertOk()
            ->assertSee('وضعیت دندان‌ها')
            ->assertSee('دندان‌های دارای وضعیت')
            ->assertSee('آسیای بزرگ اول، فک بالا')
            ->assertSee('پوسیدگی')
            ->assertSee('سطح جونده')
            ->assertSee('value="16" selected', false);

        self::assertSame('caries', $entry->fresh()->status_code);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/patients/'.$this->patient->id.'/dental-chart?tooth=36')
            ->assertOk()
            ->assertSee('ایمپلنت')
            ->assertSee('value="36" selected', false)
            ->assertSee('ثبت وضعیت دندان');
    }

    public function test_treatment_plan_form_prefills_tooth_and_surface_from_chart_context(): void
    {
        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/patients/'.$this->patient->id.'/treatment-plans/create?tooth=16&surface=O')
            ->assertOk()
            ->assertSee('FDI 16')
            ->assertSee('value="16" selected', false)
            ->assertSee('value="O" selected', false);
    }

    private function makePatient(Tenant $tenant, string $patientNo, string $mobile): Patient
    {
        return Patient::query()->create([
            'tenant_id' => $tenant->id,
            'patient_no' => $patientNo,
            'first_name' => 'بیمار',
            'last_name' => 'آزمایشی',
            'national_id' => '0012345678',
            'mobile' => $mobile,
            'status' => 'active',
        ]);
    }
}
