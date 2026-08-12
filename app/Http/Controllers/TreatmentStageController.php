<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTreatmentStageRequest;
use App\Models\TreatmentStageDefinition;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TreatmentStageController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();
        $stages = TreatmentStageDefinition::query()
            ->where(function ($query) use ($tenant): void {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('treatment-stages.index', compact('tenant', 'stages'));
    }

    public function store(StoreTreatmentStageRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();
        $stage = $tenant->treatmentStages()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'color' => $data['color'] ?? null,
            'is_active' => true,
        ]);

        $this->auditLogger->record(
            action: 'treatment_stage.created',
            tenantId: $tenant->id,
            subjectType: TreatmentStageDefinition::class,
            subjectId: $stage->id,
            after: $stage->toArray(),
            reason: 'افزودن مرحلهٔ درمان قابل تنظیم',
        );

        return back()->with('status', 'مرحلهٔ درمان با موفقیت اضافه شد.');
    }
}
