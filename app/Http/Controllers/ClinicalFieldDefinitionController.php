<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreClinicalFieldDefinitionRequest;
use App\Models\ClinicalFieldDefinition;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ClinicalFieldDefinitionController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        $tenant = $this->tenantContext->require();
        $definitions = $tenant->clinicalFieldDefinitions()
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return view('clinical-fields.index', compact('tenant', 'definitions'));
    }

    public function store(StoreClinicalFieldDefinitionRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $data = $request->validated();

        if ($data['field_type'] !== 'select') {
            $data['options'] = null;
        }

        $definition = $tenant->clinicalFieldDefinitions()->create([
            ...$data,
            'key' => strtolower($data['key']),
            'is_required' => (bool) ($data['is_required'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditLogger->record(
            action: 'clinical_field_definition.created',
            tenantId: $tenant->id,
            subjectType: ClinicalFieldDefinition::class,
            subjectId: $definition->id,
            after: $definition->toArray(),
            reason: 'افزودن فیلد سفارشی پرونده',
        );

        return back()->with('status', 'فیلد سفارشی پرونده با موفقیت اضافه شد.');
    }

    public function update(Request $request, int $definitionId): RedirectResponse
    {
        $tenant = $this->tenantContext->require();
        $definition = $tenant->clinicalFieldDefinitions()->findOrFail($definitionId);
        $before = $definition->toArray();

        $options = preg_split('/\R/u', (string) $request->input('options_text', '')) ?: [];
        $request->merge([
            'options' => array_values(array_filter(array_map('trim', $options), static fn (string $option): bool => $option !== '')),
        ]);

        $data = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:180'],
            'field_type' => ['required', Rule::in(ClinicalFieldDefinition::TYPES)],
            'options' => ['nullable', 'array', 'max:30'],
            'options.*' => ['required_with:options', 'string', 'max:120', 'distinct'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'label.required' => 'عنوان نمایشی فیلد الزامی است.',
            'field_type.in' => 'نوع فیلد انتخاب‌شده معتبر نیست.',
        ])->validate();

        if ($data['field_type'] !== 'select') {
            $data['options'] = null;
        }

        $definition->update([
            ...$data,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'updated_by' => $request->user()?->id,
        ]);

        $this->auditLogger->record(
            action: 'clinical_field_definition.updated',
            tenantId: $tenant->id,
            subjectType: ClinicalFieldDefinition::class,
            subjectId: $definition->id,
            before: $before,
            after: $definition->fresh()->toArray(),
            reason: 'به‌روزرسانی تنظیم فیلد سفارشی پرونده',
        );

        return back()->with('status', 'تنظیمات فیلد سفارشی به‌روزرسانی شد.');
    }
}
