<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Tenant;
use App\Support\AuthorizationService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenantContext, AuthorizationService $authorization): View
    {
        $user = request()->user();
        $activeTenant = $tenantContext->get();
        $currentRole = $user?->isSystemAdmin()
            ? 'سوپرادمین'
            : ($activeTenant
                ? DB::table('tenant_user')
                    ->join('roles', 'roles.id', '=', 'tenant_user.role_id')
                    ->where('tenant_user.tenant_id', $activeTenant->id)
                    ->where('tenant_user.user_id', $user->id)
                    ->where('tenant_user.status', 'active')
                    ->value('roles.name')
                : null);

        $canViewPatients = $user !== null && $authorization->allows($user, 'patients.view');
        $canViewScheduling = $user !== null && $authorization->allows($user, 'scheduling.view');
        $canViewFinance = $user !== null && $authorization->allows($user, 'finance.view');
        $dashboardMetrics = [];
        $upcomingAppointments = collect();

        if ($activeTenant !== null) {
            if ($canViewPatients) {
                $dashboardMetrics['patients_count'] = Patient::query()
                    ->where('tenant_id', $activeTenant->id)
                    ->count();
            }

            if ($canViewScheduling) {
                $dashboardMetrics['today_appointments'] = Appointment::query()
                    ->where('tenant_id', $activeTenant->id)
                    ->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])
                    ->whereNotIn('status', ['cancelled'])
                    ->count();

                $upcomingAppointments = Appointment::query()
                    ->with('patient:id,first_name,last_name')
                    ->where('tenant_id', $activeTenant->id)
                    ->where('starts_at', '>=', now())
                    ->whereNotIn('status', ['cancelled', 'completed'])
                    ->orderBy('starts_at')
                    ->limit(5)
                    ->get();
            }

            if ($canViewFinance) {
                $dashboardMetrics['outstanding_balance'] = (float) Invoice::query()
                    ->where('tenant_id', $activeTenant->id)
                    ->whereColumn('paid_total', '<', 'total')
                    ->sum(DB::raw('total - paid_total'));
            }
        }

        return view('dashboard', [
            'activeTenant' => $activeTenant,
            'currentRole' => $currentRole,
            'availableTenants' => $user?->isSystemAdmin()
                ? Tenant::query()->whereIn('status', ['active', 'trial'])->orderBy('name')->get()
                : $user?->tenants()->wherePivot('tenant_user.status', 'active')->orderBy('name')->get(),
            'isSystemAdmin' => $user?->isSystemAdmin() === true,
            'canViewPatients' => $canViewPatients,
            'canViewScheduling' => $canViewScheduling,
            'canViewFinance' => $canViewFinance,
            'dashboardMetrics' => $dashboardMetrics,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }
}
