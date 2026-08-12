<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSystemAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'default_font' => ['required', 'in:Vazirmatn,Tahoma,Arial'],
        ];
    }
}
