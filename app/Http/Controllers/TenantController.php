<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\NormalizeIdentifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class TenantController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        return view('tenants.index', [
            'tenants' => Tenant::query()->withCount('users')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('tenants.create');
    }

    public function store(StoreTenantRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $mobile = NormalizeIdentifier::mobile($data['manager_mobile']);
        $username = NormalizeIdentifier::username($data['manager_username']);

        $tenant = DB::transaction(function () use ($data, $mobile, $username): Tenant {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'status' => 'trial',
                'plan_code' => $data['plan_code'],
                'starts_on' => $data['starts_on'] ?? now()->toDateString(),
                'ends_on' => $data['ends_on'] ?? now()->addDays(30)->toDateString(),
                'branding' => [
                    'product_name' => $data['name'],
                    'brand_name' => $data['name'],
                    'font' => 'Vazirmatn',
                ],
            ]);

            $manager = User::query()->create([
                'name' => $data['manager_name'],
                'mobile' => $mobile,
                'username' => $username,
                'password' => $data['manager_password'],
                'status' => 'active',
                'is_system_admin' => false,
                'must_change_password' => true,
            ]);

            $role = Role::query()->whereNull('tenant_id')->where('code', 'clinic_manager')->firstOrFail();

            $tenant->users()->attach($manager->id, [
                'role_id' => $role->id,
                'status' => 'active',
                'scope' => json_encode(['all_branches' => true], JSON_THROW_ON_ERROR),
            ]);

            return $tenant;
        });

        $this->auditLogger->record(
            action: 'tenant.created',
            tenantId: $tenant->id,
            subjectType: Tenant::class,
            subjectId: $tenant->id,
            after: $tenant->fresh()->toArray(),
            reason: 'ایجاد کلینیک از پنل سوپرادمین',
        );

        return redirect()->route('tenants.index')->with('status', 'کلینیک با موفقیت ایجاد شد.');
    }
}
