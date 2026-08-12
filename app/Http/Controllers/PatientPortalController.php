<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\PatientAccount;
use App\Models\TreatmentPlan;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PatientPortalController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function dashboard(Request $request): View
    {
        $account = $this->patientAccount($request);
        $tenant = $this->tenantContext->require();

        $upcomingAppointments = Appointment::query()
            ->with(['branch', 'practitioner'])
            ->where('tenant_id', $tenant->id)
            ->where('patient_id', $account->patient_id)
            ->where('starts_at', '>=', now())
            ->whereNotIn('status', ['cancelled_by_patient', 'cancelled_by_clinic', 'no_show'])
            ->orderBy('starts_at')
            ->limit(3)
            ->get();

        $openInvoiceCount = Invoice::query()
            ->where('tenant_id', $tenant->id)
            ->where('patient_id', $account->patient_id)
            ->whereColumn('paid_total', '<', 'total')
            ->count();

        return view('patient-portal.dashboard', [
            'account' => $account,
            'tenant' => $tenant,
            'upcomingAppointments' => $upcomingAppointments,
            'openInvoiceCount' => $openInvoiceCount,
            'activeTreatmentPlanCount' => TreatmentPlan::query()
                ->where('tenant_id', $tenant->id)
                ->where('patient_id', $account->patient_id)
                ->whereIn('status', ['proposed', 'approved', 'in_progress'])
                ->count(),
        ]);
    }

    public function appointments(Request $request): View
    {
        $account = $this->patientAccount($request);
        $tenant = $this->tenantContext->require();

        return view('patient-portal.appointments', [
            'account' => $account,
            'appointments' => Appointment::query()
                ->with(['branch', 'practitioner'])
                ->where('tenant_id', $tenant->id)
                ->where('patient_id', $account->patient_id)
                ->orderByDesc('starts_at')
                ->paginate(12),
        ]);
    }

    public function treatmentPlans(Request $request): View
    {
        $account = $this->patientAccount($request);
        $tenant = $this->tenantContext->require();

        return view('patient-portal.treatment-plans', [
            'account' => $account,
            'treatmentPlans' => TreatmentPlan::query()
                ->withCount('items')
                ->where('tenant_id', $tenant->id)
                ->where('patient_id', $account->patient_id)
                ->latest()
                ->paginate(12),
        ]);
    }

    public function invoices(Request $request): View
    {
        $account = $this->patientAccount($request);
        $tenant = $this->tenantContext->require();

        return view('patient-portal.invoices', [
            'account' => $account,
            'invoices' => Invoice::query()
                ->withCount('payments')
                ->where('tenant_id', $tenant->id)
                ->where('patient_id', $account->patient_id)
                ->latest('issue_date')
                ->paginate(12),
        ]);
    }

    public function notifications(Request $request): View
    {
        $account = $this->patientAccount($request);
        $tenant = $this->tenantContext->require();

        return view('patient-portal.notifications', [
            'account' => $account,
            'notifications' => $request->user()->notifications()
                ->where('tenant_id', $tenant->id)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->paginate(15),
        ]);
    }

    private function patientAccount(Request $request): PatientAccount
    {
        /** @var PatientAccount $account */
        $account = $request->attributes->get('patient_account');

        return $account;
    }
}
