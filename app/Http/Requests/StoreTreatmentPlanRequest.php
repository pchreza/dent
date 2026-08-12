<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'status' => ['nullable', 'in:draft,active,on_hold,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'started_on' => ['nullable', 'date'],
        ];
    }
}
