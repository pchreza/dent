<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DentalChartEntry;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class DentalChartController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function show(Request $request, int $patientId): View
    {
        $tenant = $this->tenantContext->require();
        $patient = $tenant->patients()->findOrFail($patientId);
        $history = $patient->dentalChartEntries()
            ->with('recorder')
            ->latest('id')
            ->get();
        $latestEntries = $history
            ->groupBy('tooth_code')
            ->map(static fn ($entries): DentalChartEntry => $entries->first())
            ->sortBy('tooth_code')
            ->values();
        $selectedTooth = trim((string) $request->query('tooth', ''));
        $selectedSurface = trim((string) $request->query('surface', 'all'));

        return view('dental-chart.show', compact(
            'tenant',
            'patient',
            'history',
            'latestEntries',
            'selectedTooth',
            'selectedSurface',
        ));
    }

    public function store(Request $request, int $patientId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $patient = $tenant->patients()->findOrFail($patientId);
        $data = $request->validate([
            'tooth_code' => ['required', Rule::in(DentalChartEntry::allToothCodes())],
            'surface_code' => ['required', Rule::in(DentalChartEntry::SURFACES)],
            'status_code' => ['required', Rule::in(array_keys(DentalChartEntry::STATUSES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'tooth_code.required' => 'انتخاب دندان الزامی است.',
            'tooth_code.in' => 'کد دندان انتخاب‌شده معتبر نیست.',
            'surface_code.required' => 'انتخاب سطح دندان الزامی است.',
            'status_code.required' => 'انتخاب وضعیت دندان الزامی است.',
            'status_code.in' => 'وضعیت دندان انتخاب‌شده معتبر نیست.',
        ]);

        $entry = $patient->dentalChartEntries()->create([
            ...$data,
            'tenant_id' => $tenant->id,
            'recorded_by' => $request->user()?->id,
        ]);

        $this->auditLogger->record(
            action: 'dental_chart.entry_recorded',
            tenantId: $tenant->id,
            subjectType: DentalChartEntry::class,
            subjectId: $entry->id,
            after: $entry->toArray(),
            reason: 'ثبت رویداد جدید در نمودار دندان',
        );

        return back()->with('status', 'وضعیت دندان به‌عنوان رویداد جدید ثبت شد. سابقهٔ قبلی محفوظ است.');
    }
}
