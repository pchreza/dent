<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TreatmentPlanItem;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class TreatmentPlanItemController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function updateStatus(Request $request, int $itemId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $item = TreatmentPlanItem::query()
            ->where('tenant_id', $tenant->id)
            ->with('plan')
            ->findOrFail($itemId);
        $data = $request->validate([
            'status' => ['required', Rule::in(TreatmentPlanItem::STATUSES)],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:status,cancelled'],
        ], [
            'status.required' => 'انتخاب وضعیت جدید الزامی است.',
            'status.in' => 'وضعیت انتخاب‌شده معتبر نیست.',
            'reason.required_if' => 'برای لغو آیتم درمان، ثبت دلیل الزامی است.',
        ]);

        if ($data['status'] === $item->status) {
            return back()->with('status', 'وضعیت آیتم تغییری نکرد.');
        }

        $beforeStatus = $item->status;
        DB::transaction(function () use ($item, $tenant, $data, $request, $beforeStatus): void {
            $item->update([
                'status' => $data['status'],
                'completed_at' => $data['status'] === 'completed' ? now() : null,
            ]);
            $item->statusHistory()->create([
                'tenant_id' => $tenant->id,
                'from_status' => $beforeStatus,
                'to_status' => $data['status'],
                'reason' => $data['reason'] ?? null,
                'changed_by' => $request->user()?->id,
            ]);
        });

        $this->auditLogger->record(
            action: 'treatment_plan_item.status_updated',
            tenantId: $tenant->id,
            subjectType: TreatmentPlanItem::class,
            subjectId: $item->id,
            before: ['status' => $beforeStatus],
            after: ['status' => $data['status']],
            reason: $data['reason'] ?? 'تغییر وضعیت آیتم طرح درمان',
        );

        return back()->with('status', 'وضعیت آیتم طرح درمان به‌روزرسانی شد.');
    }
}
