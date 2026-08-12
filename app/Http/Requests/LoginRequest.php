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
            'identifier' => NormalizeIdentifier::mobile($identifier),
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
}
