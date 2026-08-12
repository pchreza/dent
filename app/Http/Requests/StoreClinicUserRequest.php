<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\NormalizeIdentifier;
use Illuminate\Foundation\Http\FormRequest;

class StoreClinicUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mobile' => NormalizeIdentifier::mobile((string) $this->input('mobile', ''))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'username' => ['required', 'alpha_dash', 'min:3', 'max:80', 'unique:users,username'],
            'password' => ['required', 'string', 'min:10', 'max:200', 'confirmed'],
            'role' => ['required', 'in:doctor,receptionist'],
            'license_no' => ['nullable', 'string', 'max:80'],
            'specialty' => ['nullable', 'string', 'max:160'],
        ];
    }
}
