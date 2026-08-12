<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentPlanRequest;
use App\Models\TreatmentCatalog;
use App\Models\TreatmentPlan;
use App\Models\TreatmentStageDefinition;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        $treatments = TreatmentCatalog::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('treatment-plans.create', compact('tenant', 'patient', 'stages', 'treatments'));
    }

    public function store(StoreTreatmentPlanRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();
        $patient = $tenant->patients()->findOrFail($data['patient_id']);
        $items = $data['items'];
        $this->ensureItemsBelongToTenant($tenant->id, $items);

        $plan = DB::transaction(function () use ($tenant, $patient, $data, $items, $request): TreatmentPlan {
            $plan = $tenant->treatmentPlans()->create([
                'patient_id' => $patient->id,
                'title' => $data['title'],
                'status' => $data['status'] ?? 'draft',
                'notes' => $data['notes'] ?? null,
                'started_on' => $data['started_on'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            $estimatedTotal = 0.0;

            foreach ($items as $sortOrder => $itemData) {
                $estimatedCost = isset($itemData['estimated_cost']) ? (float) $itemData['estimated_cost'] : null;
                $status = $itemData['status'] ?? 'planned';
                $item = $plan->items()->create([
                    'tenant_id' => $tenant->id,
                    'stage_id' => $itemData['stage_id'],
                    'treatment_id' => $itemData['treatment_id'] ?? null,
                    'tooth_code' => $itemData['tooth_code'] ?? null,
                    'surface_code' => $itemData['surface_code'] ?? null,
                    'status' => $status,
                    'priority' => $itemData['priority'] ?? 'normal',
                    'estimated_cost' => $estimatedCost,
                    'planned_on' => $itemData['planned_on'] ?? null,
                    'completed_at' => $status === 'completed' ? now() : null,
                    'notes' => $itemData['notes'] ?? null,
                    'sort_order' => $sortOrder,
                ]);
                $item->statusHistory()->create([
                    'tenant_id' => $tenant->id,
                    'from_status' => null,
                    'to_status' => $status,
                    'reason' => 'ثبت اولیهٔ آیتم طرح درمان',
                    'changed_by' => $request->user()->id,
                ]);
                $estimatedTotal += $estimatedCost ?? 0.0;
            }

            $plan->update(['estimated_total' => $estimatedTotal]);

            return $plan->fresh('items');
        });

        $this->auditLogger->record(
            action: 'treatment_plan.created',
            tenantId: $tenant->id,
            subjectType: TreatmentPlan::class,
            subjectId: $plan->id,
            after: [
                'patient_id' => $patient->id,
                'title' => $plan->title,
                'item_count' => $plan->items->count(),
                'estimated_total' => $plan->estimated_total,
            ],
            reason: 'ایجاد طرح درمان آیتم‌محور',
        );

        return redirect()->route('patients.show', ['patientId' => $patient->id])->with('status', 'طرح درمان و آیتم‌های آن ایجاد شد.');
    }

    private function ensureItemsBelongToTenant(int $tenantId, array $items): void
    {
        $stageIds = array_values(array_unique(array_map(static fn (array $item): int => (int) $item['stage_id'], $items)));
        $validStageCount = TreatmentStageDefinition::query()
            ->whereIn('id', $stageIds)
            ->where(fn ($query) => $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
            ->count();

        if ($validStageCount !== count($stageIds)) {
            throw ValidationException::withMessages([
                'items' => 'یکی از مرحله‌های درمان انتخاب‌شده به این کلینیک تعلق ندارد.',
            ]);
        }

        $treatmentIds = array_values(array_unique(array_filter(array_map(
            static fn (array $item): ?int => isset($item['treatment_id']) ? (int) $item['treatment_id'] : null,
            $items,
        ))));
        if ($treatmentIds === []) {
            return;
        }

        $validTreatmentCount = TreatmentCatalog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $treatmentIds)
            ->count();

        if ($validTreatmentCount !== count($treatmentIds)) {
            throw ValidationException::withMessages([
                'items' => 'یکی از خدمات انتخاب‌شده به این کلینیک تعلق ندارد.',
            ]);
        }
    }
}
