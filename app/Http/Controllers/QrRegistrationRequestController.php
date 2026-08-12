<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\QrRegistrationRequest;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $patient = DB::transaction(function () use ($registrationRequest, $request, $tenant): Patient {
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

            $registrationRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ])->save();

            return $patient;
        });

        $this->auditLogger->record(
            action: 'patient.qr_request_approved',
            tenantId: $tenant->id,
            subjectType: Patient::class,
            subjectId: $patient->id,
            after: ['patient_no' => $patient->patient_no, 'registration_request_id' => $registrationRequest->id],
            reason: 'تأیید ثبت‌نام QR توسط کاربر کلینیک',
        );

        return back()->with('status', 'درخواست تأیید شد و پروندهٔ بیمار ساخته شد.');
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

    private function nextPatientNumber(int $tenantId): string
    {
        $last = Patient::query()->where('tenant_id', $tenantId)->latest('id')->value('patient_no');
        $sequence = $last !== null && preg_match('/(\d+)$/', $last, $matches) ? ((int) $matches[1]) + 1 : 1;

        return 'P-'.str_pad((string) $sequence, 7, '0', STR_PAD_LEFT);
    }
}
