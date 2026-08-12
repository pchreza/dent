<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreQrRegistrationRequest;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\QrRegistrationRequest;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class PublicRegistrationController extends Controller
{
    public function create(string $tenantCode): View
    {
        [$tenant, $token] = $this->resolveTenantAndToken($tenantCode, (string) request()->query('token'));

        return view('public.registration', compact('tenant', 'token'));
    }

    public function store(StoreQrRegistrationRequest $request, string $tenantCode): RedirectResponse
    {
        [$tenant] = $this->resolveTenantAndToken($tenantCode, (string) $request->validated('token'));
        $data = $request->validated();
        $token = (string) $data['token'];

        $duplicateMatches = Patient::query()
            ->where('tenant_id', $tenant->id)
            ->where(function ($query) use ($data): void {
                $query->where('national_id', $data['national_id'])
                    ->orWhere('mobile', $data['mobile']);
            })
            ->get(['id', 'patient_no', 'first_name', 'last_name', 'mobile'])
            ->map(static fn (Patient $patient): array => [
                'id' => $patient->id,
                'patient_no' => $patient->patient_no,
                'full_name' => $patient->fullName(),
                'mobile' => $patient->mobile,
            ])
            ->values()
            ->all();

        $registrationRequest = DB::transaction(function () use ($tenant, $token, $data, $duplicateMatches): QrRegistrationRequest {
            $registrationRequest = QrRegistrationRequest::query()->create([
                'tenant_id' => $tenant->id,
                'token_hash' => hash('sha256', $token),
                'payload' => [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'national_id' => $data['national_id'],
                    'birth_date' => $data['birth_date'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'mobile' => $data['mobile'],
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'insurance_name' => $data['insurance_name'] ?? null,
                    'emergency_contact' => [
                        'name' => $data['emergency_name'] ?? null,
                        'mobile' => $data['emergency_mobile'] ?? null,
                    ],
                    'consent_at' => now()->toIso8601String(),
                ],
                'duplicate_match' => $duplicateMatches,
                'status' => 'pending',
            ]);

            $recipients = DB::table('tenant_user')
                ->join('users', 'users.id', '=', 'tenant_user.user_id')
                ->join('roles', 'roles.id', '=', 'tenant_user.role_id')
                ->where('tenant_user.tenant_id', $tenant->id)
                ->where('tenant_user.status', 'active')
                ->whereIn('roles.code', ['clinic_manager', 'doctor', 'receptionist'])
                ->pluck('users.id');

            foreach ($recipients as $recipientId) {
                Notification::query()->create([
                    'tenant_id' => $tenant->id,
                    'recipient_id' => $recipientId,
                    'type' => 'patient.registration.requested',
                    'title' => 'درخواست ثبت‌نام بیمار جدید',
                    'body' => 'یک درخواست ثبت‌نام بیمار از فرم QR دریافت شده و منتظر بررسی است.',
                    'status' => 'unread',
                    'action_url' => route('qr-requests.index'),
                    'expires_at' => now()->addDays(30),
                ]);
            }

            return $registrationRequest;
        });

        return redirect()->route('public.registration.success', [
            'tenantCode' => $tenant->code,
        ]);
    }

    public function success(string $tenantCode): View
    {
        abort_unless(Tenant::query()->where('code', $tenantCode)->whereIn('status', ['active', 'trial'])->exists(), 404);

        return view('public.registration-success');
    }

    /** @return array{0: Tenant, 1: string} */
    private function resolveTenantAndToken(string $tenantCode, string $token): array
    {
        abort_if($token === '' || ! preg_match('/^[A-Za-z0-9]{64}$/', $token), 404);

        $tenant = Tenant::query()
            ->where('code', $tenantCode)
            ->whereIn('status', ['active', 'trial'])
            ->firstOrFail();

        abort_unless($tenant->hasQrToken($token), 404);

        return [$tenant, $token];
    }
}
