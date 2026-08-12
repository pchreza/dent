<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DentalChartEntry;
use App\Models\TreatmentPlanItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'on_hold', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:4000'],
            'started_on' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.stage_id' => ['required', 'integer'],
            'items.*.treatment_id' => ['nullable', 'integer'],
            'items.*.tooth_code' => ['nullable', Rule::in(DentalChartEntry::allToothCodes())],
            'items.*.surface_code' => ['nullable', Rule::in(DentalChartEntry::SURFACES)],
            'items.*.status' => ['nullable', Rule::in(TreatmentPlanItem::STATUSES)],
            'items.*.priority' => ['nullable', Rule::in(TreatmentPlanItem::PRIORITIES)],
            'items.*.estimated_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'items.*.planned_on' => ['nullable', 'date'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'حداقل یک آیتم درمانی باید ثبت شود.',
            'items.min' => 'حداقل یک آیتم درمانی باید ثبت شود.',
            'items.*.stage_id.required' => 'مرحلهٔ درمان هر آیتم الزامی است.',
            'items.*.tooth_code.in' => 'کد دندان یکی از آیتم‌ها معتبر نیست.',
            'items.*.surface_code.in' => 'سطح دندان یکی از آیتم‌ها معتبر نیست.',
            'items.*.estimated_cost.numeric' => 'هزینهٔ برآوردی باید عدد باشد.',
        ];
    }
}
