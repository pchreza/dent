<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClinicUserRequest;
use App\Models\ClinicStaff;
use App\Models\Practitioner;
use App\Models\Role;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\NormalizeIdentifier;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ClinicUserController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();
        $users = $tenant->users()->with('roles')->wherePivot('status', 'active')->paginate(20);

        return view('clinic-users.index', compact('tenant', 'users'));
    }

    public function create(): View
    {
        return view('clinic-users.create', ['tenant' => $this->tenantContext->require()]);
    }

    public function store(StoreClinicUserRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();
        $mobile = NormalizeIdentifier::mobile($data['mobile']);
        $username = NormalizeIdentifier::username($data['username']);

        [$user, $role] = DB::transaction(function () use ($tenant, $data, $mobile, $username): array {
            $user = User::query()->create([
                'name' => $data['name'],
                'mobile' => $mobile,
                'username' => $username,
                'password' => $data['password'],
                'status' => 'active',
                'is_system_admin' => false,
                'must_change_password' => true,
            ]);
            $role = Role::query()->whereNull('tenant_id')->where('code', $data['role'])->firstOrFail();
            $tenant->users()->attach($user->id, ['role_id' => $role->id, 'status' => 'active']);

            if ($data['role'] === 'doctor') {
                Practitioner::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'license_no' => $data['license_no'] ?? null,
                    'specialty' => $data['specialty'] ?? null,
                    'is_active' => true,
                ]);
            } else {
                ClinicStaff::query()->create([
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'staff_type' => 'receptionist',
                    'is_active' => true,
                ]);
            }

            return [$user, $role];
        });

        $this->auditLogger->record(
            action: 'clinic_user.created',
            tenantId: $tenant->id,
            subjectType: get_class($user),
            subjectId: $user->id,
            after: ['role' => $role->code, 'name' => $user->name],
            reason: 'ایجاد کاربر پزشک/منشی کلینیک',
        );

        return redirect()->route('clinic-users.index')->with('status', 'کاربر کلینیک ایجاد شد.');
    }
}
