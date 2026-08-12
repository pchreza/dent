<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClinicalFieldDefinition;
use App\Models\Patient;
use App\Models\PatientClinicalFieldValue;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PatientClinicalFieldValueController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function store(Request $request, int $patientId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $patient = $tenant->patients()->findOrFail($patientId);
        $definitions = $tenant->clinicalFieldDefinitions()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $payload = $request->validate([
            'fields' => ['nullable', 'array'],
        ]);
        $values = $payload['fields'] ?? [];
        $normalizedValues = [];

        foreach ($definitions as $definition) {
            $normalizedValues[$definition->id] = $this->normalizeValue(
                definition: $definition,
                value: $values[$definition->id] ?? null,
            );
        }

        DB::transaction(function () use ($tenant, $patient, $normalizedValues, $request): void {
            foreach ($normalizedValues as $definitionId => $value) {
                $record = PatientClinicalFieldValue::query()->firstOrNew([
                    'tenant_id' => $tenant->id,
                    'patient_id' => $patient->id,
                    'clinical_field_definition_id' => $definitionId,
                ]);

                if (! $record->exists) {
                    $record->created_by = $request->user()?->id;
                }

                $record->value = ['value' => $value];
                $record->updated_by = $request->user()?->id;
                $record->save();
            }
        });

        $this->auditLogger->record(
            action: 'patient.clinical_fields_updated',
            tenantId: $tenant->id,
            subjectType: Patient::class,
            subjectId: $patient->id,
            after: ['field_definition_ids' => array_keys($normalizedValues)],
            reason: 'ثبت اطلاعات سفارشی پرونده',
        );

        return back()->with('status', 'اطلاعات سفارشی پرونده ذخیره شد.');
    }

    private function normalizeValue(ClinicalFieldDefinition $definition, mixed $value): mixed
    {
        $isBlank = $value === null || $value === '' || (is_array($value) && $value === []);

        if ($definition->is_required && $isBlank && $definition->field_type !== 'boolean') {
            throw ValidationException::withMessages([
                "fields.{$definition->id}" => "واردکردن {$definition->label} الزامی است.",
            ]);
        }

        return match ($definition->field_type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => $this->normalizeNumber($definition, $value),
            'date' => $this->normalizeDate($definition, $value),
            'select' => $this->normalizeSelect($definition, $value),
            default => $this->normalizeText($definition, $value),
        };
    }

    private function normalizeText(ClinicalFieldDefinition $definition, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || mb_strlen(trim($value)) > 5000) {
            throw ValidationException::withMessages([
                "fields.{$definition->id}" => "مقدار {$definition->label} معتبر نیست.",
            ]);
        }

        return trim($value);
    }

    private function normalizeNumber(ClinicalFieldDefinition $definition, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                "fields.{$definition->id}" => "{$definition->label} باید عدد باشد.",
            ]);
        }

        return (string) $value;
    }

    private function normalizeDate(ClinicalFieldDefinition $definition, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                "fields.{$definition->id}" => "تاریخ {$definition->label} معتبر نیست.",
            ]);
        }
    }

    private function normalizeSelect(ClinicalFieldDefinition $definition, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! in_array($value, $definition->options ?? [], true)) {
            throw ValidationException::withMessages([
                "fields.{$definition->id}" => "گزینهٔ انتخاب‌شده برای {$definition->label} معتبر نیست.",
            ]);
        }

        return $value;
    }
}
