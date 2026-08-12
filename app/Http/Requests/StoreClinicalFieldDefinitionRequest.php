<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ClinicalFieldDefinition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalFieldDefinitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $options = preg_split('/\R/u', (string) $this->input('options_text', '')) ?: [];
        $options = array_values(array_filter(array_map('trim', $options), static fn (string $option): bool => $option !== ''));

        $this->merge(['options' => $options]);
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'alpha_dash', 'min:2', 'max:80'],
            'label' => ['required', 'string', 'max:180'],
            'field_type' => ['required', Rule::in(ClinicalFieldDefinition::TYPES)],
            'options' => ['nullable', 'array', 'max:30'],
            'options.*' => ['required_with:options', 'string', 'max:120', 'distinct'],
            'is_required' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'کلید فیلد الزامی است.',
            'key.alpha_dash' => 'کلید فقط می‌تواند شامل حروف انگلیسی، عدد، خط تیره و زیرخط باشد.',
            'label.required' => 'عنوان نمایشی فیلد الزامی است.',
            'field_type.required' => 'نوع فیلد الزامی است.',
            'field_type.in' => 'نوع فیلد انتخاب‌شده معتبر نیست.',
            'options.*.distinct' => 'گزینه‌های فهرست نباید تکراری باشند.',
        ];
    }
}
