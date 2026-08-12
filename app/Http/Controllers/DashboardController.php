<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenantContext): View
    {
        $user = request()->user();
        $activeTenant = $tenantContext->get();
        $currentRole = $user?->isSystemAdmin() ? 'سوپرادمین' : ($activeTenant ? DB::table('tenant_user')->join('roles', 'roles.id', '=', 'tenant_user.role_id')->where('tenant_user.tenant_id', $activeTenant->id)->where('tenant_user.user_id', $user->id)->where('tenant_user.status', 'active')->value('roles.name') : null);

        return view('dashboard', [
            'activeTenant' => $activeTenant,
            'currentRole' => $currentRole,
            'availableTenants' => $user?->isSystemAdmin()
                ? Tenant::query()->whereIn('status', ['active', 'trial'])->orderBy('name')->get()
                : $user?->tenants()->wherePivot('tenant_user.status', 'active')->orderBy('name')->get(),
            'isSystemAdmin' => $user?->isSystemAdmin() === true,
        ]);
    }
}
