<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientAccount;
use App\Models\QrRegistrationRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class QrRegistrationRequestController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();

        return view('qr-requests.index', [
            'tenant' => $tenant,
            'requests' => $tenant->qrRegistrationRequests()->latest()->paginate(15),
        ]);
    }

    public function approve(Request $request, int $registrationRequestId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $registrationRequest = $tenant->qrRegistrationRequests()->findOrFail($registrationRequestId);

        abort_if($registrationRequest->status !== 'pending', 422, 'این درخواست قبلاً بررسی شده است.');

        [$patient, $patientAccount] = DB::transaction(function () use ($registrationRequest, $request, $tenant): array {
            $payload = $registrationRequest->payload;
            $patient = $tenant->patients()->create([
                'patient_no' => $this->nextPatientNumber($tenant->id),
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'national_id' => $payload['national_id'],
                'birth_date' => $payload['birth_date'] ?? null,
                'gender' => $payload['gender'] ?? null,
                'mobile' => $payload['mobile'],
                'phone' => $payload['phone'] ?? null,
                'address' => $payload['address'] ?? null,
                'insurance_name' => $payload['insurance_name'] ?? null,
                'emergency_contact' => $payload['emergency_contact'] ?? null,
                'status' => 'active',
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);

            $patientAccount = $this->activatePatientAccount($patient, $tenant->id, $request->user());

            $registrationRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            return [$patient, $patientAccount];
        });

        $this->auditLogger->record(
            action: 'patient.qr_request_approved',
            tenantId: $tenant->id,
            subjectType: Patient::class,
            subjectId: $patient->id,
            after: [
                'patient_no' => $patient->patient_no,
                'registration_request_id' => $registrationRequest->id,
                'patient_account_id' => $patientAccount->id,
                'user_id' => $patientAccount->user_id,
            ],
            reason: 'تأیید ثبت‌نام QR و فعال‌سازی حساب بیمار توسط کاربر کلینیک',
        );

        return back()->with('status', 'درخواست تأیید شد، پرونده ساخته شد و حساب اولیهٔ بیمار فعال است.');
    }

    public function reject(Request $request, int $registrationRequestId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $registrationRequest = $tenant->qrRegistrationRequests()->findOrFail($registrationRequestId);

        abort_if($registrationRequest->status !== 'pending', 422, 'این درخواست قبلاً بررسی شده است.');

        $reason = trim((string) $request->input('reason', ''));
        abort_if(mb_strlen($reason) < 3, 422, 'دلیل رد درخواست باید حداقل ۳ کاراکتر باشد.');

        $registrationRequest->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_reason' => $reason,
        ])->save();

        $this->auditLogger->record(
            action: 'patient.qr_request_rejected',
            tenantId: $tenant->id,
            subjectType: QrRegistrationRequest::class,
            subjectId: $registrationRequest->id,
            after: ['reason' => $reason],
            reason: 'رد ثبت‌نام QR توسط کاربر کلینیک',
        );

        return back()->with('status', 'درخواست رد شد.');
    }

    private function activatePatientAccount(Patient $patient, int $tenantId, User $actor): PatientAccount
    {
        $patientRole = Role::query()
            ->whereNull('tenant_id')
            ->where('code', 'patient')
            ->firstOrFail();

        $user = User::query()->where('mobile', $patient->mobile)->first();

        if ($user === null) {
            $user = User::query()->create([
                'name' => $patient->fullName(),
                'mobile' => $patient->mobile,
                'username' => "patient-{$tenantId}-{$patient->id}",
                'password' => Hash::make($patient->national_id),
                'status' => 'active',
                'must_change_password' => true,
            ]);
        } else {
            abort_if(
                $user->isSystemAdmin() || $this->hasStaffMembership($user, $patientRole->id),
                422,
                'این شمارهٔ موبایل به یک حساب کارکنان یا مدیریتی متصل است و برای فعال‌سازی بیمار قابل استفاده نیست.',
            );

            $linkedAccount = PatientAccount::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->first();

            abort_if(
                $linkedAccount !== null && $linkedAccount->patient_id !== $patient->id,
                422,
                'این شمارهٔ موبایل در کلینیک فعال به پروندهٔ بیمار دیگری متصل است.',
            );
        }

        $user->tenants()->syncWithoutDetaching([
            $tenantId => [
                'role_id' => $patientRole->id,
                'branch_id' => null,
                'scope' => null,
                'status' => 'active',
            ],
        ]);

        return PatientAccount::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'patient_id' => $patient->id],
            [
                'user_id' => $user->id,
                'activated_by' => $actor->id,
                'activated_at' => now(),
            ],
        );
    }

    private function hasStaffMembership(User $user, int $patientRoleId): bool
    {
        return $user->tenants()
            ->wherePivot('role_id', '!=', $patientRoleId)
            ->exists();
    }

    private function nextPatientNumber(int $tenantId): string
    {
        $last = Patient::query()->where('tenant_id', $tenantId)->latest('id')->value('patient_no');
        $sequence = $last !== null && preg_match('/(\d+)$/', $last, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'P-'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT);
    }
}
