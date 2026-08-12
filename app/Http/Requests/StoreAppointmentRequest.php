<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer'],
            'practitioner_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'appointment_type' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
