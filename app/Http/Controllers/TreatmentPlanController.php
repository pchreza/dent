<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentPlanRequest;
use App\Models\TreatmentPlan;
use App\Models\TreatmentStageDefinition;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TreatmentPlanController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(int $patientId): View
    {
        $tenant = $this->tenantContext->require();
        $patient = $tenant->patients()->findOrFail($patientId);
        $stages = TreatmentStageDefinition::query()
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('treatment-plans.create', compact('tenant', 'patient', 'stages'));
    }

    public function store(StoreTreatmentPlanRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();
        $patient = $tenant->patients()->findOrFail($data['patient_id']);
        $plan = $tenant->treatmentPlans()->create([
            'patient_id' => $patient->id,
            'title' => $data['title'],
            'status' => $data['status'] ?? 'draft',
            'notes' => $data['notes'] ?? null,
            'started_on' => $data['started_on'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->auditLogger->record(
            action: 'treatment_plan.created',
            tenantId: $tenant->id,
            subjectType: TreatmentPlan::class,
            subjectId: $plan->id,
            after: ['patient_id' => $patient->id, 'title' => $plan->title],
            reason: 'ایجاد طرح درمان بیمار',
        );

        return redirect()->route('patients.show', ['patientId' => $patient->id])->with('status', 'طرح درمان ایجاد شد.');
    }
}
