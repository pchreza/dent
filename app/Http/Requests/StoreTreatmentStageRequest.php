<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTreatmentStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'alpha_dash', 'max:80'],
            'name' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
