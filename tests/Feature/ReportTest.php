<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ReportTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Tenant $otherTenant;

    private User $manager;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2025-08-12 12:00:00');
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::query()->create(['code' => 'RPT-001', 'name' => 'کلینیک گزارش یک', 'status' => 'active', 'plan_code' => 'free']);
        $this->otherTenant = Tenant::query()->create(['code' => 'RPT-002', 'name' => 'کلینیک گزارش دو', 'status' => 'active', 'plan_code' => 'free']);
        $this->manager = User::factory()->create(['username' => 'report_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);

        $this->patient = $this->createPatient($this->tenant, 'مریم', 'گزارشی', 'P-REPORT-1', '09120000001');
        $this->createPatient($this->otherTenant, 'بیژن', 'کلینیک‌دو', 'P-REPORT-2', '09120000002');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_manager_can_view_only_active_tenant_patients_and_export_csv(): void
    {
        $from = JalaliDate::format(CarbonImmutable::parse('2025-08-01'));
        $to = JalaliDate::format(CarbonImmutable::parse('2025-08-31'));
        $query = ['from' => $from, 'to' => $to];

        $page = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/patients?'.http_build_query($query));

        $page->assertOk()->assertSee('مریم گزارشی')->assertDontSee('بیژن کلینیک‌دو');

        $csv = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/patients/export?'.http_build_query($query));

        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString('مریم گزارشی', $csv->streamedContent());
        self::assertStringNotContainsString('بیژن کلینیک‌دو', $csv->streamedContent());
        $this->assertDatabaseHas('audit_events', ['action' => 'report.exported', 'tenant_id' => $this->tenant->id]);
    }

    public function test_report_filters_produce_correct_finance_and_appointment_kpis(): void
    {
        Appointment::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'title' => 'ویزیت گزارش',
            'status' => 'completed',
            'starts_at' => '2025-08-12 10:00:00',
            'ends_at' => '2025-08-12 10:30:00',
        ]);
        Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'invoice_no' => 'INV-REPORT-1',
            'status' => 'partially_paid',
            'issue_date' => '2025-08-12',
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'paid_total' => 400,
        ]);
        $query = http_build_query(['from' => '1404/05/01', 'to' => '1404/06/01']);

        $appointments = $this->actingAs($this->manager)->withSession(['active_tenant_id' => $this->tenant->id])->get('/clinic/reports/appointments?'.$query);
        $appointments->assertOk()->assertSee('۱')->assertSee('تکمیل‌شده');

        $finance = $this->actingAs($this->manager)->withSession(['active_tenant_id' => $this->tenant->id])->get('/clinic/reports/finance?'.$query);
        $finance->assertOk()->assertSee('INV-REPORT-1')->assertSee('600');
    }

    public function test_finance_report_requires_finance_permission_even_when_reports_view_is_allowed(): void
    {
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $financePermissionId = Permission::query()->where('code', 'finance.view')->value('id');
        DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $financePermissionId)
            ->update(['allowed' => false]);

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/finance')
            ->assertForbidden();
    }

    public function test_report_reader_sees_only_authorized_cards_and_cannot_export(): void
    {
        $reader = Role::query()->create(['tenant_id' => $this->tenant->id, 'code' => 'report_reader', 'name' => 'خواننده گزارش', 'is_system' => false]);
        $permissions = Permission::query()->whereIn('code', ['reports.view', 'patients.view', 'scheduling.view'])->pluck('id')->mapWithKeys(
            static fn (int $id): array => [$id => ['allowed' => true]],
        )->all();
        $reader->permissions()->sync($permissions);
        DB::table('tenant_user')->where('tenant_id', $this->tenant->id)->where('user_id', $this->manager->id)->update(['role_id' => $reader->id]);

        $index = $this->actingAs($this->manager)->withSession(['active_tenant_id' => $this->tenant->id])->get('/clinic/reports');
        $index->assertOk()->assertSee('گزارش بیماران')->assertSee('گزارش نوبت‌ها')->assertDontSee('گزارش مالی')->assertDontSee('گزارش طرح‌های درمان');

        $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/patients/export')
            ->assertForbidden();
    }

    public function test_csv_escapes_formula_cells_and_print_route_is_available(): void
    {
        $invoice = Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'invoice_no' => 'INV-CSV-1',
            'status' => 'issued',
            'issue_date' => '2025-08-12',
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'paid_total' => 0,
        ]);
        InvoiceItem::query()->create([
            'tenant_id' => $this->tenant->id,
            'invoice_id' => $invoice->id,
            'description' => '=SUM(1,1)',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);
        $query = http_build_query(['from' => '1404/05/01', 'to' => '1404/06/01']);

        $csv = $this->actingAs($this->manager)->withSession(['active_tenant_id' => $this->tenant->id])->get('/clinic/reports/services/export?'.$query);
        $csv->assertOk();
        self::assertStringContainsString("'=SUM(1,1)", $csv->streamedContent());

        $this->actingAs($this->manager)->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/services/print?'.$query)
            ->assertOk()
            ->assertSee('گزارش خدمات');
    }

    public function test_invalid_shamsi_day_is_rejected_with_persian_validation(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/patients?from=1404/12/30');

        $response->assertRedirect()->assertSessionHasErrors('from');
    }

    public function test_invalid_shamsi_date_is_rejected_with_persian_validation(): void
    {
        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get('/clinic/reports/patients?from=invalid-date');

        $response->assertRedirect()->assertSessionHasErrors('from');
    }

    private function createPatient(Tenant $tenant, string $firstName, string $lastName, string $patientNo, string $mobile): Patient
    {
        return Patient::query()->create([
            'tenant_id' => $tenant->id,
            'patient_no' => $patientNo,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'national_id' => $patientNo,
            'mobile' => $mobile,
            'status' => 'active',
        ]);
    }
}
