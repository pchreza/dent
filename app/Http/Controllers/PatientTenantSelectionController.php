<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PatientTenantSelectionController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $accounts = $request->user()->patientAccounts()
            ->with(['tenant', 'patient'])
            ->whereHas('tenant', static fn ($query) => $query->where('status', 'active'))
            ->get();

        abort_if($accounts->isEmpty(), 403, 'حساب بیمار فعالی برای ورود یافت نشد.');

        if ($accounts->count() === 1) {
            $request->session()->put('active_tenant_id', $accounts->first()->tenant_id);

            return redirect()->route($request->user()->must_change_password ? 'patient.password.edit' : 'patient.dashboard');
        }

        return view('patient-portal.tenant-selection', ['accounts' => $accounts]);
    }

    public function store(Request $request, int $tenantId): RedirectResponse
    {
        $account = $request->user()->patientAccounts()
            ->where('tenant_id', $tenantId)
            ->whereHas('tenant', static fn ($query) => $query->where('status', 'active'))
            ->firstOrFail();

        $request->session()->put('active_tenant_id', $account->tenant_id);

        return redirect()->route($request->user()->must_change_password ? 'patient.password.edit' : 'patient.dashboard');
    }
}
