<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PatientPasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'رمز عبور جدید الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور با رمز جدید یکسان نیست.',
        ];
    }
}
