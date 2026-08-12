<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Patient $patient;

    private User $patientUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed();

        $this->tenant = Tenant::query()->create([
            'code' => 'PATIENT-PORTAL',
            'name' => 'کلینیک پورتال بیمار',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
        $this->patient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000001',
            'first_name' => 'نگار',
            'last_name' => 'محمدی',
            'national_id' => '0012345678',
            'mobile' => '09121111111',
            'status' => 'active',
        ]);
        $this->patientUser = User::factory()->create([
            'name' => $this->patient->fullName(),
            'mobile' => $this->patient->mobile,
            'username' => 'patient_portal_user',
            'password' => 'patient-password',
            'must_change_password' => false,
        ]);
        $patientRole = Role::query()->where('code', 'patient')->firstOrFail();
        $this->tenant->users()->attach($this->patientUser->id, ['role_id' => $patientRole->id, 'status' => 'active']);
        PatientAccount::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'user_id' => $this->patientUser->id,
            'activated_at' => now(),
        ]);
    }

    public function test_patient_login_redirects_to_patient_portal(): void
    {
        $this->post(route('login.store'), [
            'identifier' => $this->patientUser->mobile,
            'password' => 'patient-password',
        ])->assertRedirect(route('patient.dashboard'));
    }

    public function test_patient_sees_only_own_appointments_and_is_redirected_away_from_staff_calendar(): void
    {
        Appointment::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'title' => 'ویزیت اختصاصی',
            'status' => 'confirmed',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
        ]);
        $otherPatient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000002',
            'first_name' => 'بیمار',
            'last_name' => 'دیگر',
            'national_id' => '0012345679',
            'mobile' => '09122222222',
            'status' => 'active',
        ]);
        Appointment::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $otherPatient->id,
            'title' => 'ویزیت محرمانهٔ بیمار دیگر',
            'status' => 'confirmed',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addMinutes(30),
        ]);

        $this->actingAs($this->patientUser)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patient.appointments'))
            ->assertOk()
            ->assertSee('ویزیت اختصاصی')
            ->assertDontSee('ویزیت محرمانهٔ بیمار دیگر');

        $this->actingAs($this->patientUser)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('calendar.index'))
            ->assertRedirect(route('patient.dashboard'));
    }

    public function test_patient_can_only_read_own_invoices(): void
    {
        Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $this->patient->id,
            'invoice_no' => 'INV-OWN',
            'status' => 'open',
            'issue_date' => now()->toDateString(),
            'subtotal' => 500000,
            'discount' => 0,
            'total' => 500000,
            'paid_total' => 0,
        ]);
        $otherPatient = Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000003',
            'first_name' => 'بیمار',
            'last_name' => 'مالی',
            'national_id' => '0012345680',
            'mobile' => '09123333333',
            'status' => 'active',
        ]);
        Invoice::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_id' => $otherPatient->id,
            'invoice_no' => 'INV-OTHER',
            'status' => 'open',
            'issue_date' => now()->toDateString(),
            'subtotal' => 700000,
            'discount' => 0,
            'total' => 700000,
            'paid_total' => 0,
        ]);

        $this->actingAs($this->patientUser)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patient.invoices'))
            ->assertOk()
            ->assertSee('INV-OWN')
            ->assertDontSee('INV-OTHER');
    }

    public function test_patient_can_switch_only_between_own_patient_tenants(): void
    {
        $secondTenant = Tenant::query()->create([
            'code' => 'PATIENT-PORTAL-SECOND',
            'name' => 'کلینیک دوم بیمار',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
        $secondPatient = Patient::query()->create([
            'tenant_id' => $secondTenant->id,
            'patient_no' => 'P-0000001',
            'first_name' => 'نگار',
            'last_name' => 'محمدی',
            'national_id' => '0012345681',
            'mobile' => $this->patient->mobile,
            'status' => 'active',
        ]);
        $patientRole = Role::query()->where('code', 'patient')->firstOrFail();
        $secondTenant->users()->attach($this->patientUser->id, ['role_id' => $patientRole->id, 'status' => 'active']);
        PatientAccount::query()->create([
            'tenant_id' => $secondTenant->id,
            'patient_id' => $secondPatient->id,
            'user_id' => $this->patientUser->id,
            'activated_at' => now(),
        ]);

        $this->actingAs($this->patientUser)
            ->get(route('patient.tenants.index'))
            ->assertOk()
            ->assertSee($this->tenant->name)
            ->assertSee($secondTenant->name);

        $this->actingAs($this->patientUser)
            ->post(route('patient.tenants.store', ['tenantId' => $secondTenant->id]))
            ->assertRedirect(route('patient.dashboard'))
            ->assertSessionHas('active_tenant_id', $secondTenant->id);

        $this->actingAs($this->patientUser)
            ->withSession(['active_tenant_id' => $secondTenant->id])
            ->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee($secondTenant->name)
            ->assertDontSee($this->tenant->name);
    }

    public function test_patient_must_change_initial_password_before_opening_portal(): void
    {
        $this->patientUser->forceFill(['must_change_password' => true])->save();

        $this->actingAs($this->patientUser)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->get(route('patient.dashboard'))
            ->assertRedirect(route('patient.password.edit'));

        $this->actingAs($this->patientUser)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('patient.password.update'), [
                'password' => 'یک-رمز-امن-جدید',
                'password_confirmation' => 'یک-رمز-امن-جدید',
            ])
            ->assertRedirect(route('patient.dashboard'));

        self::assertFalse($this->patientUser->refresh()->must_change_password);
    }
}
