<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSystemAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'alpha_dash', 'min:2', 'max:40', 'unique:tenants,code'],
            'plan_code' => ['required', 'alpha_dash', 'max:80'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'manager_name' => ['required', 'string', 'max:160'],
            'manager_mobile' => ['required', 'string', 'max:20', 'unique:users,mobile'],
            'manager_username' => ['required', 'alpha_dash', 'min:3', 'max:80', 'unique:users,username'],
            'manager_password' => ['required', 'string', 'min:10', 'max:200', 'confirmed'],
        ];
    }
}
