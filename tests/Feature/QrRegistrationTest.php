<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\QrRegistrationRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class QrRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $manager;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::disk('local')->put('installed.lock', now()->toIso8601String());
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed();

        $this->token = Str::random(64);
        $this->tenant = Tenant::query()->create([
            'code' => 'QR-001',
            'name' => 'کلینیک QR',
            'status' => 'active',
            'plan_code' => 'free',
        ]);
        $this->tenant->forceFill([
            'qr_token_hash' => hash('sha256', $this->token),
            'qr_token_encrypted' => encrypt($this->token),
        ])->save();

        $this->manager = User::factory()->create(['username' => 'qr_manager']);
        $role = Role::query()->where('code', 'clinic_manager')->firstOrFail();
        $this->tenant->users()->attach($this->manager->id, ['role_id' => $role->id, 'status' => 'active']);
    }

    public function test_public_qr_form_accepts_data_and_notifies_staff(): void
    {
        $this->get(route('public.registration', ['tenantCode' => $this->tenant->code, 'token' => $this->token]))
            ->assertOk()
            ->assertSee('ثبت‌نام بیمار');

        $response = $this->post(route('public.registration.store', ['tenantCode' => $this->tenant->code]), [
            'token' => $this->token,
            'first_name' => 'سارا',
            'last_name' => 'احمدی',
            'national_id' => '۰۰۱۲۳۴۵۶۷۸',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'mobile' => '۰۹۱۲۳۴۵۶۷۸۹',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('public.registration.success', ['tenantCode' => $this->tenant->code]));
        $this->assertDatabaseHas('qr_registration_requests', [
            'tenant_id' => $this->tenant->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('notifications', [
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->manager->id,
            'type' => 'patient.registration.requested',
        ]);
    }

    public function test_manager_can_approve_request_and_create_patient(): void
    {
        $this->post(route('public.registration.store', ['tenantCode' => $this->tenant->code]), [
            'token' => $this->token,
            'first_name' => 'رضا',
            'last_name' => 'کاظمی',
            'national_id' => '۰۰۱۲۳۴۵۶۷۹',
            'mobile' => '۰۹۱۲۳۴۵۶۷۸۰',
            'consent' => '1',
        ]);
        $registrationRequest = QrRegistrationRequest::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($this->manager)
            ->withSession(['active_tenant_id' => $this->tenant->id])
            ->post(route('qr-requests.approve', ['registrationRequestId' => $registrationRequest->id]));

        $response->assertRedirect();
        $this->assertDatabaseHas('qr_registration_requests', ['id' => $registrationRequest->id, 'status' => 'approved']);
        $this->assertDatabaseHas('patients', [
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000001',
            'national_id' => '0012345679',
        ]);
    }

    public function test_public_qr_form_records_duplicate_match_without_auto_merging(): void
    {
        Patient::query()->create([
            'tenant_id' => $this->tenant->id,
            'patient_no' => 'P-0000001',
            'first_name' => 'بیمار',
            'last_name' => 'قبلی',
            'national_id' => '0012345678',
            'mobile' => '09120000000',
            'status' => 'active',
        ]);

        $this->post(route('public.registration.store', ['tenantCode' => $this->tenant->code]), [
            'token' => $this->token,
            'first_name' => 'بیمار',
            'last_name' => 'جدید',
            'national_id' => '۰۰۱۲۳۴۵۶۷۸',
            'mobile' => '۰۹۱۲۰۰۰۰۰۰۰',
            'consent' => '1',
        ]);

        $request = QrRegistrationRequest::query()->latest('id')->firstOrFail();
        self::assertNotEmpty($request->duplicate_match);
        self::assertSame('pending', $request->status);
        self::assertSame(1, Patient::query()->where('tenant_id', $this->tenant->id)->count());
    }
}
