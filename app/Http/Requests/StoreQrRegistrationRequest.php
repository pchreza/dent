<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\NormalizeIdentifier;
use Illuminate\Foundation\Http\FormRequest;

class StoreQrRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'national_id' => NormalizeIdentifier::digits((string) $this->input('national_id', '')),
            'mobile' => NormalizeIdentifier::mobile((string) $this->input('mobile', '')),
            'emergency_mobile' => NormalizeIdentifier::mobile((string) $this->input('emergency_mobile', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:120'],
            'national_id' => ['required', 'digits:10'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,unknown'],
            'mobile' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'insurance_name' => ['nullable', 'string', 'max:160'],
            'emergency_name' => ['nullable', 'string', 'max:160'],
            'emergency_mobile' => ['nullable', 'string', 'max:20'],
            'consent' => ['accepted'],
        ];
    }
}
