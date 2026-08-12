<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\NormalizeIdentifier;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $identifier = (string) $this->input('identifier', '');

        $this->merge([
            // Preserve usernames while converting Persian/Arabic digits in mobile numbers.
            // AuthController will safely try both the mobile and username variants.
            'identifier' => NormalizeIdentifier::digits($identifier),
        ]);
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:80'],
            'password' => ['required', 'string', 'min:1', 'max:200'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'واردکردن شمارهٔ موبایل یا نام کاربری الزامی است.',
            'identifier.max' => 'شمارهٔ موبایل یا نام کاربری نباید بیشتر از ۸۰ نویسه باشد.',
            'password.required' => 'واردکردن رمز عبور الزامی است.',
            'password.min' => 'رمز عبور نامعتبر است.',
            'password.max' => 'رمز عبور نامعتبر است.',
        ];
    }
}
