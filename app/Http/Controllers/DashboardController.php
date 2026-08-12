<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenantContext): View
    {
        $user = request()->user();

        return view('dashboard', [
            'activeTenant' => $tenantContext->get(),
            'availableTenants' => $user?->isSystemAdmin()
                ? Tenant::query()->whereIn('status', ['active', 'trial'])->orderBy('name')->get()
                : $user?->tenants()->wherePivot('tenant_user.status', 'active')->orderBy('name')->get(),
            'isSystemAdmin' => $user?->isSystemAdmin() === true,
        ]);
    }
}
